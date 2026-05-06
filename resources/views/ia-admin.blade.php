<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración IA - Don Guando</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .tab-active { border-bottom: 2px solid #dc2626; color: #dc2626; }
        .fade-in { animation: fadeIn 0.2s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-black text-gray-800">🤖 Configuración de IA</h1>
                        <p class="text-gray-500 text-sm">Gestiona la lógica del asistente virtual de Don Guando</p>
                    </div>
                    <a href="/dashboard" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-bold transition">← Volver al Dashboard</a>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="flex border-b bg-gray-50">
                    <button onclick="showTab('settings')" id="tab-settings" class="tab-active px-6 py-3 font-bold text-sm transition">⚙️ Configuración General</button>
                    <button onclick="showTab('faqs')" id="tab-faqs" class="px-6 py-3 font-bold text-sm text-gray-500 hover:text-gray-700 transition">❓ Preguntas Frecuentes</button>
                    <button onclick="showTab('rules')" id="tab-rules" class="px-6 py-3 font-bold text-sm text-gray-500 hover:text-gray-700 transition">📋 Reglas de Negocio</button>
                    <button onclick="showTab('responses')" id="tab-responses" class="px-6 py-3 font-bold text-sm text-gray-500 hover:text-gray-700 transition">💬 Respuestas Rápidas</button>
                </div>

                <!-- Panel Configuración General -->
                <div id="settings-panel" class="p-6 fade-in">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold">⚙️ Variables de Configuración</h2>
                        <button onclick="addSetting()" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold">+ Nueva</button>
                    </div>
                    <div id="settings-list" class="space-y-3"></div>
                </div>

                <!-- Panel FAQ -->
                <div id="faqs-panel" class="p-6 fade-in hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold">❓ Preguntas Frecuentes</h2>
                        <button onclick="addFaq()" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold">+ Nueva</button>
                    </div>
                    <div id="faqs-list" class="space-y-3"></div>
                </div>

                <!-- Panel Reglas -->
                <div id="rules-panel" class="p-6 fade-in hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold">📋 Reglas de Negocio</h2>
                        <button onclick="addRule()" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold">+ Nueva</button>
                    </div>
                    <div id="rules-list" class="space-y-3"></div>
                </div>

                <!-- Panel Respuestas -->
                <div id="responses-panel" class="p-6 fade-in hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold">💬 Respuestas Rápidas</h2>
                        <button onclick="addResponse()" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold">+ Nueva</button>
                    </div>
                    <div id="responses-list" class="space-y-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para edición -->
    <div id="modal-form" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 id="modal-title" class="text-xl font-bold mb-4">Editar</h3>
            <div id="modal-body"></div>
            <div class="flex justify-end gap-2 mt-4">
                <button onclick="closeModal()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button onclick="saveModal()" class="px-4 py-2 bg-red-600 text-white rounded-lg font-bold">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'settings';
        let currentEditId = null;
        let currentType = null;

        function showTab(tab) {
            currentTab = tab;
            document.getElementById('settings-panel').classList.add('hidden');
            document.getElementById('faqs-panel').classList.add('hidden');
            document.getElementById('rules-panel').classList.add('hidden');
            document.getElementById('responses-panel').classList.add('hidden');
            document.getElementById(`tab-settings`).classList.remove('tab-active');
            document.getElementById(`tab-faqs`).classList.remove('tab-active');
            document.getElementById(`tab-rules`).classList.remove('tab-active');
            document.getElementById(`tab-responses`).classList.remove('tab-active');
            document.getElementById(`${tab}-panel`).classList.remove('hidden');
            document.getElementById(`tab-${tab}`).classList.add('tab-active');
            
            if (tab === 'settings') loadSettings();
            else if (tab === 'faqs') loadFaqs();
            else if (tab === 'rules') loadRules();
            else if (tab === 'responses') loadResponses();
        }

        function loadSettings() {
            $.get('/api/ia-settings', function(data) {
                let html = '';
                data.forEach(s => {
                    html += `
                        <div class="bg-gray-50 rounded-lg p-4 border flex justify-between items-center">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm bg-gray-200 px-2 py-0.5 rounded">${escapeHtml(s.key)}</span>
                                    <span class="text-xs text-gray-400">${s.type}</span>
                                    ${s.is_active ? '<span class="text-green-600 text-xs">● Activo</span>' : '<span class="text-gray-400 text-xs">○ Inactivo</span>'}
                                </div>
                                <div class="text-gray-700 mt-1">${escapeHtml(s.value || '—')}</div>
                                ${s.description ? `<div class="text-xs text-gray-400 mt-1">${escapeHtml(s.description)}</div>` : ''}
                            </div>
                            <div class="flex gap-2">
                                <button onclick="editSetting(${s.id})" class="text-blue-500 hover:text-blue-700">✏️</button>
                                <button onclick="deleteSetting(${s.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                            </div>
                        </div>
                    `;
                });
                $('#settings-list').html(html || '<div class="text-center text-gray-400 py-8">No hay configuraciones</div>');
            });
        }

        function loadFaqs() {
            $.get('/api/ia-faqs', function(data) {
                let html = '';
                data.forEach(f => {
                    html += `
                        <div class="bg-gray-50 rounded-lg p-4 border">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="text-xs text-gray-400">${f.category || 'Sin categoría'} | Prioridad: ${f.priority}</div>
                                    <div class="text-sm font-bold text-gray-700 mt-1">${escapeHtml(f.question || 'Sin pregunta')}</div>
                                    <div class="text-sm text-gray-600 mt-1">${escapeHtml(f.answer)}</div>
                                    <div class="text-xs text-gray-400 mt-1">Keywords: ${f.keywords ? f.keywords.join(', ') : '—'}</div>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <button onclick="editFaq(${f.id})" class="text-blue-500 hover:text-blue-700">✏️</button>
                                    <button onclick="deleteFaq(${f.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#faqs-list').html(html || '<div class="text-center text-gray-400 py-8">No hay preguntas frecuentes</div>');
            });
        }

        function loadRules() {
            $.get('/api/ia-rules', function(data) {
                let html = '';
                data.forEach(r => {
                    html += `
                        <div class="bg-gray-50 rounded-lg p-4 border">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold">${escapeHtml(r.name)}</span>
                                        ${r.is_active ? '<span class="text-green-600 text-xs">● Activo</span>' : '<span class="text-gray-400 text-xs">○ Inactivo</span>'}
                                        <span class="text-xs text-gray-400">Prioridad: ${r.priority}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Condición: ${escapeHtml(r.condition)}</div>
                                    ${r.message ? `<div class="text-sm text-gray-600 mt-2">📢 ${escapeHtml(r.message)}</div>` : ''}
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="editRule(${r.id})" class="text-blue-500 hover:text-blue-700">✏️</button>
                                    <button onclick="deleteRule(${r.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#rules-list').html(html || '<div class="text-center text-gray-400 py-8">No hay reglas configuradas</div>');
            });
        }

        function loadResponses() {
            $.get('/api/ia-responses', function(data) {
                let html = '';
                data.forEach(r => {
                    html += `
                        <div class="bg-gray-50 rounded-lg p-4 border flex justify-between items-center">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm bg-gray-200 px-2 py-0.5 rounded">${escapeHtml(r.trigger)}</span>
                                    ${r.is_active ? '<span class="text-green-600 text-xs">● Activo</span>' : '<span class="text-gray-400 text-xs">○ Inactivo</span>'}
                                </div>
                                <div class="text-gray-700 mt-1">${escapeHtml(r.response)}</div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="editResponse(${r.id})" class="text-blue-500 hover:text-blue-700">✏️</button>
                                <button onclick="deleteResponse(${r.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                            </div>
                        </div>
                    `;
                });
                $('#responses-list').html(html || '<div class="text-center text-gray-400 py-8">No hay respuestas rápidas</div>');
            });
        }

        function escapeHtml(text) { if (!text) return ''; return String(text).replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; }); }

        function closeModal() { $('#modal-form').addClass('hidden'); currentEditId = null; currentType = null; }

        function addSetting() { currentType = 'setting'; currentEditId = null; $('#modal-title').text('➕ Nueva Configuración'); $('#modal-body').html(`
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Clave (key)</label><input type="text" id="key" class="w-full border rounded px-3 py-2" placeholder="ej: delivery_minimum_kg"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Valor</label><input type="text" id="value" class="w-full border rounded px-3 py-2"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Tipo</label><select id="type" class="w-full border rounded px-3 py-2"><option value="text">Texto</option><option value="number">Número</option><option value="list">Lista</option></select></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Descripción</label><textarea id="description" rows="2" class="w-full border rounded px-3 py-2"></textarea></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Condición (opcional)</label><textarea id="condition" rows="2" class="w-full border rounded px-3 py-2" placeholder="ej: hora_actual > 12"></textarea></div>
        `); $('#modal-form').removeClass('hidden'); }

        function editSetting(id) { currentType = 'setting'; currentEditId = id; $.get(`/api/ia-settings/${id}`, function(d) { $('#modal-title').text('✏️ Editar Configuración'); $('#modal-body').html(`
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Clave (key)</label><input type="text" id="key" class="w-full border rounded px-3 py-2" value="${escapeHtml(d.key)}"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Valor</label><input type="text" id="value" class="w-full border rounded px-3 py-2" value="${escapeHtml(d.value)}"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Tipo</label><select id="type" class="w-full border rounded px-3 py-2"><option value="text" ${d.type === 'text' ? 'selected' : ''}>Texto</option><option value="number" ${d.type === 'number' ? 'selected' : ''}>Número</option><option value="list" ${d.type === 'list' ? 'selected' : ''}>Lista</option></select></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Descripción</label><textarea id="description" rows="2" class="w-full border rounded px-3 py-2">${escapeHtml(d.description)}</textarea></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Condición</label><textarea id="condition" rows="2" class="w-full border rounded px-3 py-2">${escapeHtml(d.condition)}</textarea></div>
            <div class="flex items-center gap-2"><input type="checkbox" id="is_active" ${d.is_active ? 'checked' : ''}> <label>Activo</label></div>
        `); $('#modal-form').removeClass('hidden'); }); }

        function deleteSetting(id) { if (confirm('¿Eliminar esta configuración?')) $.ajax({ url: `/api/ia-settings/${id}`, method: 'DELETE', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: () => loadSettings() }); }

        function addFaq() { currentType = 'faq'; currentEditId = null; $('#modal-title').text('➕ Nueva Pregunta Frecuente'); $('#modal-body').html(`
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Keywords (separados por coma)</label><input type="text" id="keywords" class="w-full border rounded px-3 py-2" placeholder="precio, cuesta, valor"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Pregunta (ejemplo)</label><input type="text" id="question" class="w-full border rounded px-3 py-2" placeholder="¿Cuánto cuesta?"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Respuesta</label><textarea id="answer" rows="3" class="w-full border rounded px-3 py-2"></textarea></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Categoría</label><input type="text" id="category" class="w-full border rounded px-3 py-2" placeholder="pricing, delivery, products"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Prioridad (menor = más importante)</label><input type="number" id="priority" class="w-full border rounded px-3 py-2" value="0"></div>
        `); $('#modal-form').removeClass('hidden'); }

        function editFaq(id) { currentType = 'faq'; currentEditId = id; $.get(`/api/ia-faqs/${id}`, function(d) { $('#modal-title').text('✏️ Editar Pregunta'); $('#modal-body').html(`
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Keywords</label><input type="text" id="keywords" class="w-full border rounded px-3 py-2" value="${d.keywords ? d.keywords.join(', ') : ''}"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Pregunta</label><input type="text" id="question" class="w-full border rounded px-3 py-2" value="${escapeHtml(d.question)}"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Respuesta</label><textarea id="answer" rows="3" class="w-full border rounded px-3 py-2">${escapeHtml(d.answer)}</textarea></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Categoría</label><input type="text" id="category" class="w-full border rounded px-3 py-2" value="${escapeHtml(d.category)}"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Prioridad</label><input type="number" id="priority" class="w-full border rounded px-3 py-2" value="${d.priority}"></div>
            <div class="flex items-center gap-2"><input type="checkbox" id="is_active" ${d.is_active ? 'checked' : ''}> <label>Activo</label></div>
        `); $('#modal-form').removeClass('hidden'); }); }

        function deleteFaq(id) { if (confirm('¿Eliminar esta pregunta?')) $.ajax({ url: `/api/ia-faqs/${id}`, method: 'DELETE', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: () => loadFaqs() }); }

        function addRule() { currentType = 'rule'; currentEditId = null; $('#modal-title').text('➕ Nueva Regla de Negocio'); $('#modal-body').html(`
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Nombre</label><input type="text" id="name" class="w-full border rounded px-3 py-2" placeholder="ej: delivery_cutoff"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Condición</label><textarea id="condition" rows="2" class="w-full border rounded px-3 py-2" placeholder="hora_actual > 12"></textarea></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Mensaje para cliente</label><textarea id="message" rows="2" class="w-full border rounded px-3 py-2"></textarea></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Prioridad</label><input type="number" id="priority" class="w-full border rounded px-3 py-2" value="0"></div>
        `); $('#modal-form').removeClass('hidden'); }

        function editRule(id) { currentType = 'rule'; currentEditId = id; $.get(`/api/ia-rules/${id}`, function(d) { $('#modal-title').text('✏️ Editar Regla'); $('#modal-body').html(`
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Nombre</label><input type="text" id="name" class="w-full border rounded px-3 py-2" value="${escapeHtml(d.name)}"></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Condición</label><textarea id="condition" rows="2" class="w-full border rounded px-3 py-2">${escapeHtml(d.condition)}</textarea></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Mensaje</label><textarea id="message" rows="2" class="w-full border rounded px-3 py-2">${escapeHtml(d.message)}</textarea></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Prioridad</label><input type="number" id="priority" class="w-full border rounded px-3 py-2" value="${d.priority}"></div>
            <div class="flex items-center gap-2"><input type="checkbox" id="is_active" ${d.is_active ? 'checked' : ''}> <label>Activo</label></div>
        `); $('#modal-form').removeClass('hidden'); }); }

        function deleteRule(id) { if (confirm('¿Eliminar esta regla?')) $.ajax({ url: `/api/ia-rules/${id}`, method: 'DELETE', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: () => loadRules() }); }

        function addResponse() { currentType = 'response'; currentEditId = null; $('#modal-title').text('➕ Nueva Respuesta Rápida'); $('#modal-body').html(`
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Trigger</label><select id="trigger" class="w-full border rounded px-3 py-2"><option value="thanks">Gracias</option><option value="greeting">Saludo</option><option value="goodbye">Despedida</option><option value="error">Error</option></select></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Respuesta</label><textarea id="response" rows="3" class="w-full border rounded px-3 py-2"></textarea></div>
            <div class="flex items-center gap-2"><input type="checkbox" id="use_emojis" checked> <label>Usar emojis</label></div>
        `); $('#modal-form').removeClass('hidden'); }

        function editResponse(id) { currentType = 'response'; currentEditId = id; $.get(`/api/ia-responses/${id}`, function(d) { $('#modal-title').text('✏️ Editar Respuesta'); $('#modal-body').html(`
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Trigger</label><select id="trigger" class="w-full border rounded px-3 py-2"><option value="thanks" ${d.trigger === 'thanks' ? 'selected' : ''}>Gracias</option><option value="greeting" ${d.trigger === 'greeting' ? 'selected' : ''}>Saludo</option><option value="goodbye" ${d.trigger === 'goodbye' ? 'selected' : ''}>Despedida</option><option value="error" ${d.trigger === 'error' ? 'selected' : ''}>Error</option></select></div>
            <div class="mb-3"><label class="block text-xs font-bold mb-1">Respuesta</label><textarea id="response" rows="3" class="w-full border rounded px-3 py-2">${escapeHtml(d.response)}</textarea></div>
            <div class="flex items-center gap-2"><input type="checkbox" id="use_emojis" ${d.use_emojis ? 'checked' : ''}> <label>Usar emojis</label></div>
            <div class="flex items-center gap-2"><input type="checkbox" id="is_active" ${d.is_active ? 'checked' : ''}> <label>Activo</label></div>
        `); $('#modal-form').removeClass('hidden'); }); }

        function deleteResponse(id) { if (confirm('¿Eliminar esta respuesta?')) $.ajax({ url: `/api/ia-responses/${id}`, method: 'DELETE', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: () => loadResponses() }); }

        function saveModal() {
            let data = { _token: $('meta[name="csrf-token"]').attr('content'), id: currentEditId };
            if (currentType === 'setting') { data.key = $('#key').val(); data.value = $('#value').val(); data.type = $('#type').val(); data.description = $('#description').val(); data.condition = $('#condition').val(); data.is_active = $('#is_active').is(':checked'); $.post('/api/ia-settings/save', data, () => { closeModal(); loadSettings(); }); }
            else if (currentType === 'faq') { data.keywords = $('#keywords').val().split(',').map(k => k.trim()); data.question = $('#question').val(); data.answer = $('#answer').val(); data.category = $('#category').val(); data.priority = $('#priority').val(); data.is_active = $('#is_active').is(':checked'); $.post('/api/ia-faqs/save', data, () => { closeModal(); loadFaqs(); }); }
            else if (currentType === 'rule') { data.name = $('#name').val(); data.condition = $('#condition').val(); data.message = $('#message').val(); data.priority = $('#priority').val(); data.is_active = $('#is_active').is(':checked'); $.post('/api/ia-rules/save', data, () => { closeModal(); loadRules(); }); }
            else if (currentType === 'response') { data.trigger = $('#trigger').val(); data.response = $('#response').val(); data.use_emojis = $('#use_emojis').is(':checked'); data.is_active = $('#is_active').is(':checked'); $.post('/api/ia-responses/save', data, () => { closeModal(); loadResponses(); }); }
        }

        $(document).ready(() => { loadSettings(); });
    </script>
</body>
</html>