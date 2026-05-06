<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Don Guando Dash</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-50 to-gray-100 h-screen flex overflow-hidden">

<!-- PRIMERA COLUMNA: Lista de Contactos -->
<div class="w-1/4 bg-white border-r flex flex-col shadow-lg z-10">
    <div class="p-4 bg-red-700 text-white shadow-md">
        <h2 class="font-bold text-xl uppercase tracking-wider text-center">Don Guando</h2>
    </div>

    <div class="p-4 border-b flex justify-around bg-gray-50">
        <button onclick="showSection('chats')" class="text-blue-600 font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" stroke-width="2"/></svg>
            Chats
        </button>
        <button onclick="openInventory()" class="text-gray-500 hover:text-blue-600 font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-width="2"/></svg>
            Inventario
        </button>
    </div>
    
    <!-- Buscador de contactos -->
    <div class="p-3 border-b bg-white">
        <input type="text" id="search-contacts" 
               placeholder="🔍 Buscar por nombre o número..." 
               class="w-full text-sm border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-red-500">
    </div>
    
    <!-- Filtros de etiquetas -->
    <div id="tag-filters-container" class="p-3 border-b bg-gray-50 hidden">
        <div class="flex justify-between items-center mb-2">
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">📌 FILTRAR POR ETIQUETA</p>
            <button onclick="openTagsManager()" class="text-[9px] text-blue-500 hover:underline">⚙️ Administrar</button>
        </div>
        <div id="tag-filters-list" class="flex flex-wrap gap-1">
            <button onclick="filterByTag('')" class="text-[9px] px-2 py-1 rounded-full bg-gray-200 hover:bg-gray-300 transition-all">Todos</button>
        </div>
    </div>
    
    <!-- Lista de contactos -->
    <div id="contact-list" class="flex-1 overflow-y-auto">
        @foreach($contacts as $contact)
            <div class="p-3 border-b hover:bg-gray-50 transition contact-card">
                <div class="flex justify-between items-start">
                    <div onclick="loadChat({{ $contact->id }}, '{{ addslashes($contact->name) }}', {{ $contact->is_intervened ? 'true' : 'false' }})" class="flex-1 cursor-pointer">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-bold text-gray-800">{{ $contact->name }}</p>
                            @if($contact->is_pinned)
                                <span class="text-xs text-blue-500">📌</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500">{{ $contact->whatsapp_id }}</p>
                        <div class="text-[9px] text-green-600 mt-1">
                            🤖 IA: {{ $contact->ia_messages_count ?? 0 }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1 ml-2">
                        <button onclick="event.stopPropagation(); editContactName({{ $contact->id }}, '{{ addslashes($contact->name) }}')" 
                                class="text-blue-400 hover:text-blue-700 p-1 rounded transition-colors" title="Editar nombre">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2"/>
                            </svg>
                        </button>
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ $contact->is_intervened ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }}">
                            {{ $contact->is_intervened ? 'MANUAL' : 'AUTO' }}
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Botón de exportación (movido al final de la columna) -->
    <div class="p-3 border-t bg-gray-50">
        <button onclick="exportContacts()" class="flex items-center justify-center gap-2 w-full text-sm text-gray-600 hover:text-blue-600 transition-colors">
            <span>📊</span> <span>Exportar contactos</span>
        </button>
    </div>
</div>

<!-- SEGUNDA COLUMNA: Chat Principal -->
<div class="w-2/4 flex flex-col bg-white border-r">
    <div id="chat-header" class="p-4 bg-gray-50 flex justify-between items-center border-b shadow-sm relative">
        <div class="flex items-center space-x-2">
            <span id="contact-name-header" class="font-bold text-gray-700 text-lg">Selecciona un cliente</span>
            <div id="contact-tags-container" class="flex gap-1"></div>
            <button id="btn-add-tag" onclick="showTagModal()" class="hidden text-gray-400 hover:text-green-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="flex items-center space-x-4">   
            <button id="btn-show-order" onclick="toggleOrderPanel()" class="hidden p-2 text-yellow-600 hover:bg-yellow-100 rounded-full transition-colors">📋</button>
            <button id="btn-intervene" onclick="toggleIntervention()" class="hidden px-4 py-1 rounded-full text-[10px] font-black shadow-sm transition-all duration-300">IA ON</button>
            
            <!-- Botón para configurar número destino -->
<button id="btn-set-destination" onclick="openDestinationPanel()" class="hidden p-2 text-indigo-600 hover:bg-indigo-100 rounded-full transition-colors" title="Configurar número destino">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-width="2"/>
    </svg>
</button>
<button id="btn-catalogos" onclick="openCatalogosConfig()" class="hidden p-2 text-indigo-600 hover:bg-indigo-100 rounded-full transition-colors" title="Catálogos">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-width="2"/>
    </svg>
</button>
            <div class="relative inline-block text-left">
                <button onclick="toggleMenu()" id="btn-menu" class="hidden p-2 text-gray-500 hover:bg-gray-200 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                    </svg>
                </button>
                <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white border rounded-lg shadow-xl z-50 overflow-hidden">
                    <div class="py-1 text-gray-700">
                        <button onclick="togglePinChatFromMenu()" id="btn-pin-chat" class="flex items-center space-x-2 w-full text-left px-4 py-3 text-sm hover:bg-gray-100 transition-colors">
                            <span>📌</span> <span id="pin-chat-text">Anclar chat</span>
                        </button>
                        <div class="border-t"></div>
                        <button onclick="clearChat()" class="flex items-center space-x-2 w-full text-left px-4 py-3 text-sm hover:bg-gray-100 transition-colors italic">
                            <span>🧹</span> <span>Vaciar chat</span>
                        </button>
                        <button onclick="deleteContact()" class="flex items-center space-x-2 w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 font-bold border-t transition-colors">
                            <span>🗑️</span> <span>Eliminar contacto</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="chat-messages" class="flex-1 p-6 overflow-y-auto flex flex-col space-y-4 shadow-inner">
        <div class="flex flex-col items-center justify-center h-full opacity-30">
            <p class="text-lg font-bold">Carnicería Don Guando</p>
            <p class="text-sm">Selecciona un chat para ver los mensajes</p>
        </div>
    </div>

    <!-- Área de entrada de mensajes con sistema de comandos -->
<div class="border-t bg-white">
    <div class="flex items-center gap-2 px-3 py-2">
        <div id="message-input-container" class="flex-1 relative">
            <input type="text" id="message-input" placeholder="Escribe un mensaje... Escribe / para ver comandos" 
                   class="w-full border-0 focus:ring-0 text-sm py-2 outline-none">
            
            <!-- Panel de respuestas rápidas (aparece al escribir /) -->
            <div id="quick-replies-panel" class="hidden">
                <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-purple-500 text-white p-2 rounded-t-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">⚡</span>
                        <span class="text-xs font-bold uppercase">Respuestas Rápidas</span>
                        <span class="text-[9px] ml-auto">Escribe / + comando</span>
                    </div>
                </div>
                <div class="p-2 border-b bg-gray-50">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">📱 Comandos</span>
                    </div>
                    <div id="quick-commands-list" class="flex flex-wrap gap-2"></div>
                </div>
                <div id="quick-replies-categories" class="divide-y max-h-48 overflow-y-auto"></div>
                <div class="sticky bottom-0 p-2 border-t bg-gray-50 text-center">
                    <button onclick="openCmdConfigAndClose()" class="text-[10px] text-blue-500 hover:underline font-bold">⚙️ Administrar respuestas rápidas</button>
                </div>
            </div>
            
            <!-- Sugerencias de comandos (aparece al escribir /palabra) -->
            <div id="command-suggestions" class="hidden"></div>
        </div>
        <!-- Botón 🎉 para abrir configuración de promociones -->
<button onclick="openPromoConfig()" id="btn-promo-config" class="hidden p-2 text-pink-500 hover:bg-pink-100 rounded-full transition-colors" title="Configurar promociones">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke-width="2" stroke-linecap="round"/>
        <circle cx="12" cy="12" r="3" stroke-width="2"/>
    </svg>
</button>
        <!-- Botón rayo para abrir configuración -->
        <button onclick="openCmdConfig()" id="btn-cmd-config" class="hidden p-2 text-yellow-500 hover:bg-yellow-100 rounded-full transition-colors" title="Configurar comandos rápidos">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        
        <button onclick="sendMessage()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-1 rounded-full text-sm font-bold transition-colors">
            Enviar
        </button>
    </div>
</div>

    
    <!-- Panel de respuestas rápidas (oculto por defecto) -->
    <div id="quick-replies-panel" class="hidden border-t bg-gray-50 max-h-64 overflow-y-auto">
        <!-- Comandos rápidos -->
        <div class="p-2 border-b bg-white">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">📱 Comandos rápidos</span>
                <span class="text-[8px] text-gray-300">Escribe / en el chat</span>
            </div>
            <div id="quick-commands-list" class="flex flex-wrap gap-2">
                <span class="text-[9px] text-gray-400">Cargando comandos...</span>
            </div>
        </div>
        
        <!-- Respuestas rápidas por categoría -->
        <div id="quick-replies-categories" class="divide-y">
            <div class="text-center text-gray-400 text-xs py-4">Cargando respuestas rápidas...</div>
        </div>
    </div>
    
    <!-- Sugerencias de comandos (aparece al escribir /) -->
    <div id="command-suggestions" class="hidden absolute bottom-full left-0 right-0 bg-white border rounded-t-lg shadow-lg max-h-48 overflow-y-auto z-50"></div>
    <!-- Sugerencias de promociones (aparece al escribir /promo...) -->
<div id="promo-suggestions" class="hidden bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto z-50" style="position: absolute; bottom: 100%; left: 0; right: 0; margin-bottom: 5px;"></div>
</div>
   </div> 

<!-- TERCERA COLUMNA: Panel de Ventas -->
<div class="w-1/4 flex flex-col bg-white p-4 h-full overflow-hidden">
    <h3 class="text-sm font-black text-gray-400 uppercase mb-4 tracking-widest flex-shrink-0">⚡ Panel de Ventas</h3>
    
    <div class="mb-4 flex-shrink-0">
        <input type="text" id="chat-product-search" placeholder="Buscar carne..." 
               class="w-full text-xs border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-red-500">
        <div id="chat-product-results" class="mt-2 space-y-2 max-h-48 overflow-y-auto">
            <!-- Resultados de búsqueda -->
        </div>
    </div>

    <hr class="mb-4 flex-shrink-0">

    <!-- Contenedor con scroll para mensajes -->
    <div class="flex-1 overflow-y-auto">
        <div id="quick-messages-area" class="hidden">
            <h4 class="text-[10px] font-bold text-gray-400 mb-2 uppercase sticky top-0 bg-white py-1">
                Mensajes para: <span id="selected-product-name" class="text-red-600"></span>
            </h4>
            <div id="quick-messages-list" class="space-y-3 pb-4">
                <!-- Mensajes rápidos se insertan aquí -->
            </div>
            
            <button onclick="openConfigQuickMessages()" class="mt-4 text-[10px] text-blue-500 hover:underline w-full text-center font-bold sticky bottom-0 bg-white py-2">
                ⚙️ Editar Mensajes Rápidos
            </button>
        </div>
    </div>
</div>

    <!-- Panel de Pedido (flotante) -->
    <div id="order-panel" class="hidden absolute right-0 top-0 h-full w-72 bg-yellow-50 border-l p-4 shadow-xl z-40">
        <div class="flex justify-between items-center border-b border-yellow-200 pb-3 mb-4">
            <h3 class="font-bold text-red-800 text-xs tracking-widest uppercase">📋 Ficha de Pedido</h3>
            <button onclick="toggleOrderPanel()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="text-[10px] text-yellow-600 font-bold uppercase">Cliente</label>
                <p id="info-cliente" class="text-sm font-bold text-gray-800 bg-white p-2 rounded border border-yellow-100">---</p>
            </div>
            <div>
                <label class="text-[10px] text-yellow-600 font-bold uppercase">Producto</label>
                <p id="info-producto" class="text-sm font-bold text-gray-800 bg-white p-2 rounded border border-yellow-100">---</p>
            </div>
            <div>
                <label class="text-[10px] text-yellow-600 font-bold uppercase">Cantidad</label>
                <p id="info-cantidad" class="text-sm font-bold text-gray-800 bg-white p-2 rounded border border-yellow-100">---</p>
            </div>
            <div>
                <label class="text-[10px] text-yellow-600 font-bold uppercase">Dirección</label>
                <p id="info-direccion" class="text-sm font-bold text-gray-800 bg-white p-2 rounded border border-yellow-100 italic break-words select-all cursor-text min-h-[40px]">---</p>
            </div>
        </div>

        <button onclick="clearOrderInfo()" class="mt-8 w-full py-2 bg-yellow-200 hover:bg-red-600 hover:text-white text-red-800 text-xs font-bold rounded-lg transition-all">
            🔄 Vaciar para nuevo pedido
        </button>
    </div>

    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    </script>
    <script src="{{ asset('chat.js') }}"></script>

<!-- Modal Inventario -->
<div id="modal-inventory" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-4xl h-[80vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        
        <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-xl font-black text-gray-800 uppercase">📦 Inventario Don Guando</h2>
            <button onclick="closeInventory()" class="text-gray-400 hover:text-red-500 text-3xl font-bold">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <div class="flex justify-between mb-4">
                <input type="text" id="inventory-search" placeholder="Buscar producto..." 
                       class="border rounded-lg px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-red-500">
                <div class="flex gap-2">
                    <a href="/import-products" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold text-sm transition-colors">
                        📥 Importar
                    </a>
                    <button onclick="openProductForm()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold transition-colors">
                        + Nuevo
                    </button>
                </div>
            </div>

<table class="min-w-full bg-white border">
    <thead class="bg-gray-100 text-[10px] uppercase font-bold text-gray-500">
        <tr>
            <th class="px-4 py-2 text-left">Producto</th>
            <th class="px-4 py-2 text-left">Precio</th>
            <th class="px-4 py-2 text-left">Stock</th>
            <th class="px-4 py-2 text-left">Beneficio/Uso</th>
            <th class="px-4 py-2 text-left">Psicología de Venta</th>
            <th class="px-4 py-2 text-center">Acción</th>
        </tr>
    </thead>
    <tbody id="inventory-table-body">
        <!-- Filas de inventario se insertan aquí -->
    </tbody>
</table>
        </div>
    </div>
</div>

    <!-- Modal Formulario Producto -->
    <div id="modal-product-form" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
            <h3 class="text-xl font-black text-gray-800 mb-4 uppercase">🛒 Nuevo Producto</h3>
            
            <form id="product-form" class="space-y-4">
                <input type="hidden" id="p-id">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">NOMBRE DEL CORTE</label>
                    <input type="text" id="p-name" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none" placeholder="Ej: Picaña" required>
                </div>
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 mb-1">PRECIO (S/)</label>
                        <input type="number" step="0.01" id="p-price" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none" required>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 mb-1">STOCK</label>
                        <input type="number" id="p-stock" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none" required>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">UNIDAD</label>
                    <select id="p-unit" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none">
                        <option value="kg">Kilogramo (kg)</option>
                        <option value="unidad">Unidad</option>
                        <option value="paquete">Paquete</option>
                    </select>
                </div>
                <div>
    <label class="block text-xs font-bold text-gray-500 mb-1">BENEFICIO/USO</label>
    <textarea id="p-beneficio" rows="2" class="w-full border rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500" 
              placeholder="Ej: Ideal para asados, alto en proteínas..."></textarea>
</div>
<div>
    <label class="block text-xs font-bold text-gray-500 mb-1">PSICOLOGÍA DE VENTA</label>
    <textarea id="p-psicologia" rows="2" class="w-full border rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500" 
              placeholder="Ej: Precio irresistible para ocasiones especiales..."></textarea>
</div>
                
                <div class="flex gap-2 pt-4">
                    <button type="button" onclick="closeProductForm()" class="flex-1 px-4 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
                    <button type="submit" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg font-bold shadow-md hover:bg-red-700">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
<div id="modal-quick-config" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 border-t-4 border-blue-600">
        <h3 class="text-xl font-black text-gray-800 mb-1 uppercase text-center">⚙️ Configurar Plantilla</h3>
        <p class="text-[10px] text-gray-400 mb-4 text-center font-bold uppercase">Haz clic en las etiquetas para insertarlas</p>
        
        <div class="flex justify-center gap-2 mb-4">
            <button type="button" onclick="insertTag('{producto}')" 
                    class="bg-purple-100 text-purple-700 px-3 py-1 rounded-md border border-purple-300 text-xs font-bold hover:bg-purple-200 transition-all">
                { producto }
            </button>
            <button type="button" onclick="insertTag('{precio}')" 
                    class="bg-green-100 text-green-700 px-3 py-1 rounded-md border border-green-300 text-xs font-bold hover:bg-green-200 transition-all">
                { precio }
            </button>
        </div>

        <form id="quick-response-form"> <input type="hidden" id="q-id">
            <div>
                <label class="block text-[10px] font-black text-gray-500 mb-1 uppercase">Título del Atajo</label>
                <input type="text" id="q-title" class="w-full border rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Precio e invitación">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-500 mb-1 uppercase">Cuerpo del Mensaje</label>
                <textarea id="q-body" rows="4" class="w-full border rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" 
                          placeholder="Escribe aquí tu mensaje..."></textarea>
            </div>
            
            <div class="flex gap-2">
                <button type="button" onclick="closeConfigQuickMessages()" class="flex-1 py-2 text-gray-400 font-bold hover:bg-gray-50 rounded-lg">Cancelar</button>
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition-all">Guardar Plantilla</button>
            </div>
        </form>
    </div>
</div>


<!-- Modal Gestión de Tags -->
<div id="modal-tags-manager" class="hidden fixed inset-0 z-[80] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        <div class="p-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white flex justify-between items-center">
            <h3 class="text-lg font-black uppercase">🏷️ Gestión de Etiquetas</h3>
            <button onclick="closeTagsManager()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="p-4 border-b">
            <div class="flex gap-2">
                <input type="text" id="new-tag-name" placeholder="Nueva etiqueta..." 
                       class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                <input type="color" id="new-tag-color" value="#6b21f5" 
                       class="w-12 h-10 rounded border cursor-pointer">
                <button onclick="createTag()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-bold">
                    + Agregar
                </button>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 max-h-96">
            <div id="tags-list" class="flex flex-wrap gap-2">
                <!-- Lista de tags se carga aquí -->
            </div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end">
            <button onclick="closeTagsManager()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Configuración de Comandos (SISTEMA DE COMANDOS) -->
<div id="modal-cmd-config" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-4 bg-gradient-to-r from-green-600 to-teal-600 text-white flex justify-between items-center rounded-t-2xl">
            <h3 class="text-lg font-black uppercase">⚡ Configurar Comandos Rápidos</h3>
            <button onclick="closeCmdConfig()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="flex border-b">
            <button onclick="showCmdTab('categories')" id="cmd-tab-categories" class="flex-1 py-2 font-bold text-sm hover:bg-gray-100 transition-colors border-b-2 border-green-500 text-green-600">
                📁 Categorías
            </button>
            <button onclick="showCmdTab('commands')" id="cmd-tab-commands" class="flex-1 py-2 font-bold text-sm hover:bg-gray-100 transition-colors text-gray-500">
                💬 Comandos
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4">
            <!-- Panel de Categorías -->
            <div id="cmd-categories-panel">
                <!-- Formulario para agregar nueva categoría -->
                <div class="bg-gray-50 rounded-lg p-3 mb-4 border">
                    <p class="text-xs font-bold text-gray-500 mb-2 uppercase">➕ Agregar nueva categoría</p>
                    <div class="flex gap-2">
                        <input type="text" id="cmd-new-category-name" placeholder="Nombre (ej: Carnes, Pollos, Cerdo)" 
                               class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <input type="text" id="cmd-new-category-icon" placeholder="Icono" value="🥩" 
                               class="w-20 border rounded-lg px-3 py-2 text-sm text-center">
                        <button onclick="createCmdCategory()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold text-sm">
                            + Agregar
                        </button>
                    </div>
                </div>
                
                <!-- Lista de categorías existentes -->
                <p class="text-xs font-bold text-gray-500 mb-2 uppercase">📋 Categorías existentes</p>
                <div id="cmd-categories-list" class="space-y-2 max-h-96 overflow-y-auto">
                    <div class="text-center text-gray-400 py-4">Cargando categorías...</div>
                </div>
            </div>
            
            <!-- Panel de Comandos -->
            <div id="cmd-commands-panel" class="hidden">
                <div class="flex gap-2 mb-4">
                    <select id="cmd-filter-category" class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="">Todas las categorías</option>
                    </select>
                    <button onclick="openCmdCommandForm()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold">
                        + Nuevo Comando
                    </button>
                </div>
                <div id="cmd-commands-list" class="space-y-2 max-h-96 overflow-y-auto"></div>
            </div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end">
            <button onclick="closeCmdConfig()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para editar comando individual -->
<div id="modal-cmd-command-form" class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        <h3 class="text-xl font-black text-gray-800 mb-4 uppercase" id="cmd-form-title">Nuevo Comando</h3>
        <input type="hidden" id="cmd-edit-id">
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Categoría</label>
            <select id="cmd-edit-category" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Seleccionar categoría</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Comando (ej: /cerdo)</label>
            <input type="text" id="cmd-command" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" 
                   placeholder="/comando">
            <p class="text-[9px] text-gray-400 mt-1">Escribe / + comando en el chat</p>
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Título</label>
            <input type="text" id="cmd-title" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" 
                   placeholder="Título visible en el panel">
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Mensaje</label>
            <textarea id="cmd-body" rows="4" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" 
                      placeholder="Contenido del mensaje a enviar..."></textarea>
        </div>
        
        <div class="flex gap-2 pt-2">
            <button onclick="closeCmdCommandForm()" class="flex-1 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="saveCmdCommand()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-bold">Guardar</button>
        </div>
    </div>
</div>
<!-- Modal Configuración de Promociones (🎉) -->
<div id="modal-promo-config" class="hidden fixed inset-0 z-[120] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-4 bg-gradient-to-r from-pink-600 to-rose-600 text-white flex justify-between items-center rounded-t-2xl">
            <h3 class="text-lg font-black uppercase">🎉 Configurar Promociones</h3>
            <button onclick="closePromoConfig()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="flex border-b">
            <button onclick="showPromoTab('pdf')" id="promo-tab-pdf" class="flex-1 py-2 font-bold text-sm hover:bg-gray-100 transition-colors border-b-2 border-pink-500 text-pink-600">
                📄 PDF
            </button>
            <button onclick="showPromoTab('image')" id="promo-tab-image" class="flex-1 py-2 font-bold text-sm hover:bg-gray-100 transition-colors text-gray-500">
                🖼️ Imágenes
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4">
            <!-- Panel de PDF -->
            <div id="promo-pdf-panel">
                <div class="flex gap-2 mb-4">
                    <button onclick="openPromoForm('pdf')" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg font-bold text-sm">
                        + Comando PDF
                    </button>
                </div>
                <div id="promo-pdf-list" class="space-y-2">
                    <div class="text-center text-gray-400 py-4">Cargando PDFs...</div>
                </div>
            </div>
            
            <!-- Panel de Imágenes -->
            <div id="promo-image-panel" class="hidden">
                <div class="flex gap-2 mb-4">
                    <button onclick="openPromoForm('image')" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg font-bold text-sm">
                        + Comando Imagen
                    </button>
                </div>
                <div id="promo-image-list" class="space-y-2">
                    <div class="text-center text-gray-400 py-4">Cargando imágenes...</div>
                </div>
            </div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end">
            <button type="button" onclick="openFileManager()" class="bg-gray-600 text-white px-3 py-2 rounded-lg text-sm ">Gestor de archivos PDF/Imagenes 📁</button>
            <button onclick="closePromoConfig()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para crear/editar comando -->
<div id="modal-promo-form" class="hidden fixed inset-0 z-[130] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        <h3 class="text-xl font-black text-gray-800 mb-4 uppercase" id="promo-form-title">Nuevo Comando</h3>
        <input type="hidden" id="promo-edit-id">
        <input type="hidden" id="promo-type">
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Comando (ej: /promo1)</label>
            <input type="text" id="promo-command" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="/comando">
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Título</label>
            <input type="text" id="promo-title" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Título visible">
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Archivo</label>
            <div class="flex gap-2">
                <select id="promo-file-select" class="flex-1 border rounded-lg px-3 py-2 text-sm">
                    <option value="">Seleccionar archivo</option>
                </select>
                <button type="button" onclick="openFileManager()" class="bg-gray-600 text-white px-3 py-2 rounded-lg text-sm">📁</button>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Leyenda (opcional)</label>
            <textarea id="promo-caption" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Mensaje que acompañará al archivo..."></textarea>
        </div>
        
        <div class="flex gap-2 pt-2">
            <button onclick="closePromoForm()" class="flex-1 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="savePromotion()" class="flex-1 bg-pink-600 hover:bg-pink-700 text-white py-2 rounded-lg font-bold">Guardar</button>
        </div>
    </div>
</div>
<!-- Modal Gestión de Archivos -->
<div id="modal-file-manager" class="hidden fixed inset-0 z-[200] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col max-h-[80vh]">
        <div class="p-4 bg-gradient-to-r from-blue-600 to-cyan-600 text-white flex justify-between items-center rounded-t-2xl">
            <h3 class="text-lg font-black uppercase">📁 Gestión de Archivos</h3>
            <button onclick="closeFileManager()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="flex border-b">
            <button onclick="showFileTab('pdf')" id="file-tab-pdf" class="flex-1 py-2 font-bold text-sm border-b-2 border-blue-500 text-blue-600">📄 PDF</button>
            <button onclick="showFileTab('image')" id="file-tab-image" class="flex-1 py-2 font-bold text-sm text-gray-500">🖼️ Imágenes</button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4">
            <div class="flex justify-end mb-4">
                <button onclick="showUploadFileForm()" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm">+ Subir archivo</button>
            </div>
            <div id="file-list-container" class="space-y-2"></div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end">
            <button onclick="closeFileManager()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para subir archivo (simple) -->
<div id="modal-upload-file" class="hidden fixed inset-0 z-[210] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        <h3 class="text-xl font-black text-gray-800 mb-4 uppercase">📤 Subir Archivo</h3>
        <input type="hidden" id="upload-file-type">
        
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Seleccionar archivo</label>
            <input type="file" id="upload-file-input" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded-lg px-3 py-2">
        </div>
        
        <div class="flex gap-2">
            <button onclick="closeUploadModal()" class="flex-1 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="uploadFile()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-bold">Subir</button>
        </div>
    </div>
</div>

<!-- Modal Gestión de Catálogos (📚) -->
<div id="modal-catalogos" class="hidden fixed inset-0 z-[150] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white flex justify-between items-center rounded-t-2xl">
            <h3 class="text-lg font-black uppercase">📚 Gestión de Catálogos</h3>
            <button onclick="closeCatalogosConfig()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4">
            <div id="catalogos-list" class="space-y-3">
                <div class="text-center text-gray-400 py-8">Cargando catálogos...</div>
            </div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end">
            <button onclick="closeCatalogosConfig()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para editar PDF/Imagen de catálogo -->
<div id="modal-catalogo-file" class="hidden fixed inset-0 z-[160] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        <h3 class="text-xl font-black text-gray-800 mb-4 uppercase" id="catalogo-file-title">Editar PDF</h3>
        <input type="hidden" id="catalogo-edit-id">
        <input type="hidden" id="catalogo-edit-format">
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Categoría</label>
            <p id="catalogo-categoria-name" class="text-sm font-bold text-gray-700 bg-gray-100 p-2 rounded"></p>
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Seleccionar archivo</label>
            <div class="flex gap-2">
                <select id="catalogo-file-select" class="flex-1 border rounded-lg px-3 py-2 text-sm">
                    <option value="">Seleccionar archivo existente</option>
                </select>
                <button type="button" onclick="openUploadModalForCatalogo()" class="bg-green-600 text-white px-3 py-2 rounded-lg text-sm">📤 Subir</button>
            </div>
            <p class="text-[9px] text-gray-400 mt-1">Selecciona un archivo PDF o imagen según el tipo</p>
        </div>
        
        <div class="flex gap-2 pt-2">
            <button onclick="closeCatalogoFileForm()" class="flex-1 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="saveCatalogoFile()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-bold">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal para editar Link de catálogo -->
<div id="modal-catalogo-link" class="hidden fixed inset-0 z-[160] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        <h3 class="text-xl font-black text-gray-800 mb-4 uppercase">🔗 Editar Link</h3>
        <input type="hidden" id="catalogo-link-id">
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Categoría</label>
            <p id="catalogo-link-categoria" class="text-sm font-bold text-gray-700 bg-gray-100 p-2 rounded"></p>
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">URL del Link</label>
            <input type="url" id="catalogo-link-url" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="https://ejemplo.com/catalogo">
        </div>
        
        <div class="flex gap-2 pt-2">
            <button onclick="closeCatalogoLinkForm()" class="flex-1 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="saveCatalogoLink()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-bold">Guardar</button>
        </div>
    </div>
</div>
<!-- Panel para configurar número destino -->
<div id="destination-panel" class="hidden fixed bottom-20 right-4 bg-white rounded-lg shadow-xl border p-4 z-50 w-72">
    <div class="flex justify-between items-center mb-3">
        <h4 class="text-sm font-bold text-gray-700">📞 Número destino</h4>
        <button onclick="closeDestinationPanel()" class="text-gray-400 hover:text-gray-600">&times;</button>
    </div>
    <input type="tel" id="destination-phone" placeholder="Ej: 51902235011" class="w-full border rounded-lg px-3 py-2 text-sm mb-2 focus:ring-2 focus:ring-indigo-500">
    <p class="text-[9px] text-gray-400 mb-2">Solo números, sin espacios ni símbolos</p>
    <div class="flex gap-2">
        <button onclick="saveDestinationNumber()" class="flex-1 bg-indigo-600 text-white py-1 rounded-lg text-sm font-bold">Guardar</button>
        <button onclick="clearDestinationNumber()" class="flex-1 bg-gray-200 text-gray-600 py-1 rounded-lg text-sm">Limpiar</button>
    </div>
    <div id="current-destination-display" class="mt-2 text-[10px] text-gray-500 text-center hidden">
        Actual: <span id="current-destination-number"></span>
    </div>
</div>
<!-- Modal para editar nombre de contacto -->
<div id="modal-edit-contact" class="hidden fixed inset-0 z-[200] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        <h3 class="text-xl font-black text-gray-800 mb-4 uppercase">✏️ Editar Contacto</h3>
        <input type="hidden" id="edit-contact-id">
        <input type="hidden" id="edit-contact-phone">
        
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Número de WhatsApp</label>
            <p id="edit-contact-phone-display" class="text-sm text-gray-600 bg-gray-100 p-2 rounded"></p>
        </div>
        
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Nuevo nombre</label>
            <input type="text" id="edit-contact-name" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" 
                   placeholder="Nombre del contacto">
        </div>
        
        <div class="flex gap-2">
            <button onclick="closeEditContactModal()" class="flex-1 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="saveContactName()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-bold">Guardar</button>
        </div>
    </div>
</div>
</body>
</html>