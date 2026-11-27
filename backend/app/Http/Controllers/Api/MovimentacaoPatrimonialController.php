<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimentacaoPatrimonial;
use App\Models\MovimentacaoEstoque;
use Illuminate\Http\Request;

class MovimentacaoPatrimonialController extends Controller
{
    
    public function index(Request $request)
    {
        $query = MovimentacaoPatrimonial::with([
            'bemPatrimonial',
            'setorOrigem',
            'setorDestino',
            'usuario',
        ]);

        // Busca por descrição ou número de tombo
        if ($request->filled('busca')) {
            $termo = trim($request->busca);

            $query->whereHas('bemPatrimonial', function ($bem) use ($termo) {
                $bem->where('descricao', 'like', "%{$termo}%")
                    ->orWhere('numero_tombo', 'like', "%{$termo}%");
            });
        }

        // Filtro por tipo
        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->where('tipo', $request->tipo);
        }

        // Filtro por setor de destino
        if ($request->filled('setor_destino_id')) {
            $query->where(
                'setor_destino_id',
                $request->setor_destino_id
            );
        }

        // Filtro por período
        $periodo = $request->input('periodo');

        if (in_array($periodo, ['7', '30', '90', '365'])) {
            $query->whereDate(
                'data_movimentacao',
                '>=',
                now()->subDays((int) $periodo)->toDateString()
            );
        }

        $movimentacoes = $query
            ->orderByDesc('data_movimentacao')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json($movimentacoes);
    }

    public function estoque(Request $request)
    {
        $query = MovimentacaoEstoque::with([
            'material',
            'usuario',
            'solicitacaoRetirada',
        ]);

        // Busca por nome ou descrição do material
        if ($request->filled('busca')) {
            $termo = trim($request->busca);

            $query->whereHas('material', function ($material) use ($termo) {
                $material->where('nome', 'like', "%{$termo}%")
                    ->orWhere('descricao', 'like', "%{$termo}%");
            });
        }

        // Filtro por tipo
        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->where('tipo', $request->tipo);
        }

        // Filtro por período
        $periodo = $request->input('periodo');

        if (in_array($periodo, ['7', '30', '90', '365'])) {
            $query->whereDate(
                'data_movimentacao',
                '>=',
                now()->subDays((int) $periodo)->toDateString()
            );
        }

        return response()->json(
            $query
                ->orderByDesc('data_movimentacao')
                ->orderByDesc('id')
                ->paginate(10)
        );
    }

}
