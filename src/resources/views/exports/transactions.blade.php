<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Extrato de Transações</title>
    <style>
        /* Base Tailwind CSS - Personalize conforme necessário */
        html { line-height: 1.5; -webkit-text-size-adjust: 100%; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"; font-size: 14px; }
        h2 { font-size: 1.5em; font-weight: 700; margin-top: 1em; margin-bottom: 0.5em; } /* text-2xl font-bold */

        /* Classes para a tabela */
        .w-full { width: 100%; }
        .border-collapse { border-collapse: collapse; }
        .text-sm { font-size: 0.875rem; } /* Aproximadamente 14px, ajuste se 12px for o ideal */
        .text-xs { font-size: 0.75rem; } /* Aproximadamente 12px */
        .border { border-width: 1px; border-style: solid; border-color: #e5e7eb; } /* border-gray-300 */
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .text-left { text-align: left; }
        .bg-gray-100 { background-color: #f3f4f6; } /* bg-gray-100 */
        .font-semibold { font-weight: 600; }
        .p-6 { padding: 1.5rem; }
        .mb-6 { margin-bottom: 1.5rem; }
    </style>
</head>
<body class="p-6">
    <h2 class="text-2xl font-bold mb-6">Extrato de Transações</h2>
    <table class="w-full border-collapse text-sm border">
        <thead>
            <tr>
                <th class="border px-4 py-2 text-left bg-gray-100 font-semibold">ID</th>
                <th class="border px-4 py-2 text-left bg-gray-100 font-semibold">Tipo</th>
                <th class="border px-4 py-2 text-left bg-gray-100 font-semibold">Valor</th>
                <th class="border px-4 py-2 text-left bg-gray-100 font-semibold">Descrição</th>
                <th class="border px-4 py-2 text-left bg-gray-100 font-semibold">Data</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
            <tr>
                <td class="border px-4 py-2 text-xs">{{ $t->id }}</td>
                <td class="border px-4 py-2 text-xs">{{ $t->type }}</td>
                <td class="border px-4 py-2 text-xs">{{ number_format($t->value, 2, ',', '.') }}</td>
                <td class="border px-4 py-2 text-xs">{{ $t->description }}</td>
                <td class="border px-4 py-2 text-xs">{{ $t->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
