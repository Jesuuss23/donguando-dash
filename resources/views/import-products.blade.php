<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Productos - Don Guando</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full">
        <div class="text-center mb-6">
            <div class="text-6xl mb-3">📦</div>
            <h2 class="text-2xl font-bold text-gray-800">Importar Productos</h2>
            <p class="text-gray-500 text-sm mt-1">Carga un archivo CSV</p>
        </div>
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif
        
        <form action="{{ url('/import-products') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    📄 Archivo CSV
                </label>
                <input type="file" 
                       name="file" 
                       accept=".csv"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"
                       required>
                <p class="text-xs text-gray-400 mt-1">
                    Formato: .csv | Máx: 10MB | UTF-8
                </p>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <p class="text-xs font-bold text-gray-600 mb-2">📋 FORMATO DEL CSV:</p>
                <pre class="text-[10px] text-gray-500 overflow-x-auto whitespace-pre-wrap">
producto,precio,stock,unidad,beneficio_uso,psicologia_venta
Carne de pavo molido,30,10,kg,"Opción saludable","Come rico sin culpa"
Pollo entero,105.8,12,kg,"Ideal para reuniones","Perfecto para toda la familia"
                </pre>
            </div>
            
            <button type="submit" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                🚀 Importar Productos
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="/dashboard" class="text-sm text-gray-500 hover:text-red-600 transition-colors">
                ← Volver al Dashboard
            </a>
        </div>
    </div>
</body>
</html>