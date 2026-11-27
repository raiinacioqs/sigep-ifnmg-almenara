<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manutencao;
use Illuminate\Http\Request;

class ManutencaoController extends Controller
{
    public function index(Request $request)
    {
        $query = Manutencao::with([
            'bemPatrimonial:id,numero_tombo,descricao',
        ]);

        // Busca por descrição ou número de tombo do bem
        if ($request->filled('busca')) {
            $termo = trim($request->busca);

            $query->whereHas('bemPatrimonial', function ($bem) use ($termo) {
                $bem->where('descricao', 'like', "%{$termo}%")
                    ->orWhere('numero_tombo', 'like', "%{$termo}%");
            });
        }

        // Filtro por tipo de manutenção
        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->where('tipo', $request->tipo);
        }

        // Filtro por status
        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        // Filtro por período de abertura
        $periodo = $request->input('periodo');

        if (in_array($periodo, ['7', '30', '90', '365'])) {
            $query->whereDate(
                'data_abertura',
                '>=',
                now()->subDays((int) $periodo)->toDateString()
            );
        }

        $manutencoes = $query
            ->orderByDesc('data_abertura')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json($manutencoes);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'bem_patrimonial_id' => [
                'required',
                'integer',
                'exists:bens_patrimoniais,id',
            ],

            'tipo' => [
                'required',
                'in:preventiva,corretiva',
            ],

            'data_abertura' => [
                'required',
                'date',
            ],

            'descricao' => [
                'required',
                'string',
                'max:5000',
            ],

            'prestador' => [
                'nullable',
                'string',
                'max:150',
            ],

            'custo' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'data_conclusao' => [
                'nullable',
                'date',
                'after_or_equal:data_abertura',
            ],

            'observacao' => [
                'nullable',
                'string',
            ],
        ]);

        // Define o status inicial da manutenção.
        $dados['status'] = !empty($dados['data_conclusao'])
            ? 'concluida'
            : 'aberta';

        $manutencao = Manutencao::create($dados);

        $manutencao->load([
            'bemPatrimonial:id,numero_tombo,descricao',
        ]);

        return response()->json([
            'message' => 'Manutenção cadastrada com sucesso!',
            'data' => $manutencao,
        ], 201);
    }
}
