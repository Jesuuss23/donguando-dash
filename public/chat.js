let currentContactId = null;
let isFetching = false;
window.currentContactId = null;
let selectedProduct = null;
let currentSelectedPrice = 0;
let currentContactName = null;
let currentContactPhone = null;
/* TOGGLE IA */
function toggleIntervention() {
    if (!currentContactId) return;

    const btn = $('#btn-intervene');
    btn.prop('disabled', true);

    $.post('/chat/intervene/' + currentContactId, function(data) {
        updateButtonUI(data.is_intervened);
        const contactCard = $(`.contact-card[onclick*="loadChat(${currentContactId}"]`);
        const statusSpan = contactCard.find('span');

        if (data.is_intervened) {
            statusSpan.removeClass('bg-green-100 text-green-600').addClass('bg-red-100 text-red-600').text('MANUAL');
        } else {
            statusSpan.removeClass('bg-red-100 text-red-600').addClass('bg-green-100 text-green-600').text('AUTO');
        }

        btn.prop('disabled', false);
    });
}

/* CARGAR CHAT */
function loadChat(contactId, name, isIntervened) {
    window.currentContactId = contactId;
    currentContactId = contactId;
    currentContactName = name;
    currentContactPhone = phone;

    const contactCard = $(`.contact-card[onclick*="loadChat(${contactId},"]`);
    const realPhone = contactCard.find('p.text-xs').text().trim(); // Asumiendo que el número está en un <p> con esa clase

    if (realPhone && realPhone.startsWith('519')) {
        window.currentContactId = realPhone;
        console.log("✅ Teléfono real capturado de la UI:", window.currentContactId);
    } else {
        // Si no lo encuentra así, lo intentamos recuperar por la info que ya carga tu función
        window.currentContactId = contactId; 
    }
    // UI Inicial
    $('#btn-show-order').removeClass('hidden');
    $('#contact-name-header').text('Chat con ' + name);
    $('#chat-messages').html('<p class="text-center text-gray-500">Cargando...</p>');
    $('#btn-intervene').removeClass('hidden');
    $('#btn-menu').removeClass('hidden');
    $('#btn-add-tag').removeClass('hidden');

    updateButtonUI(isIntervened);
    fetchMessages(contactId);
    updateBotStatus(contactId);
    renderTags(contactId);

$.ajax({
    url: '/contact-info/' + contactId,
    type: 'GET',
    cache: false, // <--- ESTO ES LO MÁS IMPORTANTE: Evita datos antiguos
    success: function(contact) {
        if (contact) {
            // Actualizamos los textos. Si en la DB es null, se pondrá '---'
            $('#info-producto').text(contact.producto || '---');
            $('#info-cantidad').text(contact.cantidad || '---');
            $('#info-direccion').html(formatLinks(contact.direccion || '---'));
            
            // Si todo está vacío, ocultamos el panel para que no estorbe
            if (!contact.producto && !contact.cantidad && !contact.direccion) {
                $('#order-panel').addClass('hidden');
            } else {
                $('#order-panel').removeClass('hidden');
            }
        }
    }
});
    $('#btn-add-tag').removeClass('hidden');
    
    // Cargar etiquetas del contacto
    $.get(`/contacts/${contactId}/tags`, function(tags) {
        let container = $('#contact-tags-container');
        container.empty();
        tags.forEach(tag => {
            container.append(`
                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border" 
                      style="background-color: ${tag.color}20; color: ${tag.color}; border-color: ${tag.color}">
                    ${tag.name.toUpperCase()}
                </span>
            `);
        });
    });
    setTimeout(() => {
        renderTags(contactId);
    }, 100);
}

function showTagModal() {
    console.log("ID actual seleccionado:", window.currentContactId);

    if (!window.currentContactId) {
        alert("Primero selecciona un contacto de la lista de la izquierda.");
        return;
    }

    let tagName = prompt("Nombre de la etiqueta:");
    if (!tagName) return;

    $.ajax({
        url: `/contacts/${window.currentContactId}/tags`,
        method: 'POST',
        data: {
            name: tagName,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(tag) {
            renderTags(window.currentContactId);
        },
        error: function(xhr) {
            alert("Error al guardar. Revisa la consola.");
            console.log(xhr.responseText);
        }
    });
}

function renderTags(contactId) {
    if (!contactId) return;

    $.get(`/contacts/${contactId}/tags`, function(tags) {
        let container = $('#contact-tags-container');
        container.empty();
        
        if (Array.isArray(tags)) {
            tags.forEach(tag => {
                container.append(`
                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full border shadow-sm" 
                          style="background-color: ${tag.color}20; color: ${tag.color}; border-color: ${tag.color}">
                        ${tag.name}
                        <button onclick="removeTag(${contactId}, ${tag.id})" class="ml-1 hover:text-black font-bold" title="Eliminar etiqueta">
                            ×
                        </button>
                    </span>
                `);
            });
        }
    }).fail(function(xhr) {
        console.error("Error al obtener etiquetas:", xhr.responseText);
    });
}

/* UI BOTÓN */
function updateButtonUI(isIntervened) {
    const btn = $('#btn-intervene');
    btn.removeClass('hidden bg-green-500 bg-red-600');

    if (isIntervened) {
        btn.addClass('bg-red-600 text-white').text('MANUAL (IA OFF)');
    } else {
        btn.addClass('bg-green-500 text-white').text('AUTO (IA ON)');
    }
}

/* ESTADO IA */
function updateBotStatus(contactId) {
    $.get('/check-status-by-id/' + contactId, function(data) {
        updateButtonUI(data.is_intervened);
    });
}

/* MENSAJES Sin duplicados */
function fetchMessages(contactId) {
    if (isFetching || !contactId) return;
    isFetching = true;

    $.get('/chat/messages/' + contactId, function(messages) {
        const currentCount = $('#chat-messages .message-bubble').length;

        if (messages.length !== currentCount) {
            let html = '';
            messages.forEach(msg => {
                let alignment = msg.from_me ? 'justify-end' : 'justify-start';
                let color = msg.from_me ? 'bg-green-200' : 'bg-white';
                html += `
                    <div class="flex ${alignment} mb-2 message-bubble">
                        <div class="${color} p-2 rounded-lg shadow-sm max-w-md border border-gray-100">
                            <p class="text-sm text-gray-800">${formatLinks(msg.body)}</p>
                            <p class="text-[9px] text-gray-400 text-right">${new Date(msg.created_at).toLocaleTimeString()}</p>
                        </div>
                    </div>`;
            });
            $('#chat-messages').html(html); 
            $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
        }
        isFetching = false;
    }).fail(() => { isFetching = false; });
}

/* REFRESCO GLOBAL */
setInterval(() => {
    if (currentContactId) {
        fetchMessages(currentContactId);
        updateBotStatus(currentContactId);
    }
}, 3000);

function toggleOrderPanel() {
    $('#order-panel').toggleClass('hidden');
}

function clearOrderInfo() {
    if (!currentContactId) return;
    
    if (confirm('¿Quieres limpiar los datos del pedido? Esto no borrará los mensajes, solo la ficha.')) {
        $.post('/chat/clear-order/' + currentContactId, function() {
            // 1. Limpiamos los textos
            $('#info-producto, #info-cantidad, #info-direccion').text('---');
            
            // 2. Ocultamos el panel de la ficha
            $('#order-panel').addClass('hidden'); 
            
            alert('Ficha limpiada para un nuevo pedido.');
        });
    }
}

/* MENÚ Y LIMPIEZA */
function toggleMenu() { $('#dropdown-menu').toggleClass('hidden'); }

$(document).click(function(event) {
    if (!$(event.target).closest('#btn-menu, #dropdown-menu, #btn-show-order').length) {
        $('#dropdown-menu').addClass('hidden');
    }
});

function clearChat() {
    if (!currentContactId) return;
    if (confirm('¿Quieres borrar todos los mensajes de este chat?')) {
        $.ajax({
            url: '/chat/clear/' + currentContactId,
            type: 'DELETE',
            success: function() {
                $('#chat-messages').html('<p class="text-center text-gray-500 mt-10">Chat vaciado</p>');
                $('#dropdown-menu').addClass('hidden');
            }
        });
    }
}

function deleteContact() {
    if (!currentContactId) return;
    if (confirm('⚠️ Se eliminará el contacto y todos sus mensajes')) {
        $.ajax({
            url: '/chat/delete-contact/' + currentContactId,
            type: 'DELETE',
            success: function() {
                alert('Contacto eliminado');
                location.reload();
            }
        });
    }
}

//funcion para identificar links
function formatLinks(text) {
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    return text.replace(urlRegex, function(url) {
        return `<a href="${url}" target="_blank" class="text-blue-600 underline hover:text-blue-800">${url}</a>`;
    });
}

function removeTag(contactId, tagId) {
    if (!confirm("¿Seguro que quieres quitar esta etiqueta?")) return;

    $.ajax({
        url: `/contacts/${contactId}/tags/${tagId}`,
        method: 'DELETE', // Usamos el método DELETE que creamos en Laravel
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log("Etiqueta eliminada");
            renderTags(contactId); // Volvemos a pintar las etiquetas
        },
        error: function(xhr) {
            console.error("Error al eliminar etiqueta:", xhr.responseText);
            alert("No se pudo eliminar la etiqueta.");
        }
    });
}


function openInventory() {
    // Mostramos el modal
    $('#modal-inventory').removeClass('hidden');
    // Cargamos los datos
    loadInventoryData();
}

function closeInventory() {
    // Escondemos el modal
    $('#modal-inventory').addClass('hidden');
    $('#inventory-search').val(''); // Limpia el texto escrito
    loadInventoryData();
}

function loadInventoryData() {
    $.get('/inventory/products', function(products) {
        let tbody = $('#inventory-table-body');
        tbody.empty();

        products.forEach(p => {
            tbody.append(`
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3 font-bold text-gray-700">${p.name}</td>
                    <td class="px-4 py-3 text-blue-600 font-black">S/ ${p.price}</td>
                    <td class="px-4 py-3">${p.stock} ${p.unit}</td>
                    <td class="px-4 py-3 text-center">
<div class="flex justify-center gap-2">
                            <button onclick="editProduct(${p.id})" class="text-[10px] bg-blue-100 text-blue-600 px-2 py-1 rounded hover:bg-blue-200 font-bold">
                                Editar
                            </button>
                            <button onclick="deleteProduct(${p.id}, '${p.name}')" class="text-[10px] bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200 font-bold">
                                Eliminar
                            </button>
                        </div>
                    </td>
                </tr>
            `);
        });
    });
}

function openProductForm() {
    $('#modal-product-form').removeClass('hidden');
}

function closeProductForm() {
    $('#modal-product-form').addClass('hidden');
    $('#product-form')[0].reset(); // Limpia los campos
    $('#p-id').val(''); // ¡VITAL! Limpia el ID oculto
    $('#modal-product-form h3').text('🛒 Nuevo Producto');
}

$(document).ready(function() {
    // Buscador interactivo
    $('#inventory-search').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $("#inventory-table-body tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Envío del formulario (Crear o Editar)
    $('#product-form').on('submit', function(e) {
        e.preventDefault();
        
        // 1. Detectamos si hay un ID para saber la URL
        let id = $('#p-id').val();
        let url = id ? `/inventory/update/${id}` : '/inventory/save';

        let formData = {
            name: $('#p-name').val(),
            price: $('#p-price').val(),
            stock: $('#p-stock').val(),
            unit: $('#p-unit').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        console.log("Enviando a: " + url, formData);

        // 2. UN SOLO POST que usa la URL dinámica
        $.post(url, formData)
            .done(function(data) {
                console.log("Servidor dice OK:", data);
                
                // Mensaje diferente según si es nuevo o editado
                let msg = id ? "¡Cambios guardados en " + data.name + "!" : "¡Producto creado: " + data.name + "!";
                alert(msg);
                
                closeProductForm();
                loadInventoryData(); 
            })
            .fail(function(xhr) {
                console.error("Error del servidor:", xhr.responseText);
                alert("Error al procesar la solicitud. Revisa la consola.");
            });
    });
});

// Asegúrate de ponerle id="inventory-search" a tu input de búsqueda en el HTML
$('#inventory-search').on('keyup', function() {
    let value = $(this).val().toLowerCase();
    
    $("#inventory-table-body tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
});

function editProduct(id) {
    // 1. Cambiamos el título del modal para que el usuario sepa que está editando
    $('#modal-product-form h3').text('📝 Editar Producto');
    
    // 2. Buscamos el producto en la base de datos para obtener los valores actuales
    $.get(`/inventory/product/${id}`, function(p) {
        // 3. Llenamos los campos del formulario
        $('#p-id').val(p.id);
        $('#p-name').val(p.name);
        $('#p-price').val(p.price);
        $('#p-stock').val(p.stock);
        $('#p-unit').val(p.unit);
        
        // 4. Abrimos el modal
        $('#modal-product-form').removeClass('hidden');
    });
}

function deleteProduct(id, name) {
    if (confirm(`¿Estás seguro de eliminar "${name}"? Esta acción no se puede deshacer.`)) {
        $.ajax({
            url: `/inventory/delete/${id}`,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                alert("Producto eliminado correctamente");
                loadInventoryData(); // Recargamos la tabla para que desaparezca
            },
            error: function() {
                alert("Error al intentar eliminar el producto");
            }
        });
    }
}

$('#chat-product-search').on('input', function() {
    let query = $(this).val();
    if (query.length < 2) { $('#chat-product-results').empty(); return; }

    $.get(`/inventory/products?search=${query}`, function(products) {
        let results = $('#chat-product-results');
        results.empty();

        products.forEach(p => {
            results.append(`
                <div onclick="selectProductForMessage('${p.name}', '${p.price}')" 
                     class="p-2 border rounded-lg hover:bg-red-50 cursor-pointer transition-all">
                    <div class="text-xs font-bold text-gray-700">${p.name}</div>
                    <div class="text-[10px] text-blue-600">S/ ${p.price} - Stock: ${p.stock}</div>
                </div>
            `);
        });
    });
});


// Variable global para mantener el precio del producto seleccionado actualmente


function selectProductForMessage(name, price) {
    // Guardamos el precio en la variable global que definimos antes
    currentSelectedPrice = price;
    
    // Ocultamos el buscador de productos para dar espacio a los mensajes
    // Opcional: puedes dejarlo y que los mensajes aparezcan abajo
    $('#quick-messages-area').removeClass('hidden');
    
    // Cargamos los mensajes pasando los datos del clic
    loadQuickMessages(name, price);
}

function loadQuickMessages(productName, productPrice) {
    $.get('/quick-responses', function(responses) {
        let list = $('#quick-messages-list');
        list.empty();

        responses.forEach(res => {
            // AQUÍ SE HACE EL CAMBIO:
            // Buscamos todas las veces que escribiste {producto} y lo cambiamos por el nombre real
            let msgFinal = res.body
                .replace(/{producto}/g, productName) 
                .replace(/{precio}/g, productPrice);

            list.append(`
                <div class="mb-3 p-3 bg-white border border-gray-200 rounded-xl shadow-sm group">
                    <div class="text-[10px] font-black text-blue-500 uppercase mb-1">${res.title}</div>
                    <div class="text-xs text-gray-600 leading-snug mb-3">${msgFinal}</div>
                    
                    <div class="flex gap-2">
                        <button onclick="sendToN8N('${msgFinal.replace(/'/g, "\\'")}')" 
                                class="flex-1 bg-green-500 hover:bg-green-600 text-white text-[10px] font-bold py-1 px-2 rounded-lg transition-all flex items-center justify-center gap-1">
                            <span>🚀 Enviar</span>
                        </button>
                        
                        <button onclick="editTemplate(${res.id}, '${res.title}', '${res.body.replace(/'/g, "\\'")}')" 
                                class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-1 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </button>

                        <button onclick="deleteTemplate(${res.id})" 
                                class="bg-red-50 hover:bg-red-100 text-red-500 p-1 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </button>
                    </div>
                </div>
            `);
        });
    });
}

function insertIntoChat(text) {
    $('#message-input').val(text).focus();
}

// --- CONFIGURACIÓN DE PLANTILLAS ---

function openConfigQuickMessages() {
    $('#modal-quick-config').removeClass('hidden');
}

function closeConfigQuickMessages() {
    $('#modal-quick-config').addClass('hidden');
    $('#quick-response-form')[0].reset();
    $('#q-id').val('');
}

$(document).ready(function() {
    console.log("Sistema de plantillas listo");

    // Usamos esta forma de declarar el evento para que sea más robusta
    $(document).on('submit', '#quick-response-form', function(e) {
        e.preventDefault();
        console.log("Botón Guardar presionado");

        const data = {
            id: $('#q-id').val(),
            title: $('#q-title').val(),
            body: $('#q-body').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        if(!data.title || !data.body) {
            alert("Por favor rellena todos los campos");
            return;
        }

        console.log("Enviando a Laravel:", data);

        $.ajax({
            url: '/quick-responses/save',
            method: 'POST',
            data: data,
            success: function(res) {
                console.log("Respuesta de Laravel:", res);
                alert("¡Guardado correctamente!");
                closeConfigQuickMessages();
                
                // Recargamos la lista si hay algo seleccionado
                let currentP = $('#selected-product-name').text();
                if(currentP && currentP !== "") {
                    loadQuickMessages(currentP, currentSelectedPrice);
                }
            },
            error: function(xhr) {
                console.error("Error crítico:", xhr.responseText);
                alert("Error al guardar: " + xhr.status);
            }
        });
    });
});
function insertTag(tag) {
    const textarea = document.getElementById('q-body');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    
    // Insertamos la etiqueta en la posición del cursor
    const newText = text.substring(0, start) + tag + text.substring(end);
    
    textarea.value = newText;
    
    // Devolvemos el foco al textarea y movemos el cursor después de la etiqueta
    textarea.focus();
    textarea.setSelectionRange(start + tag.length, start + tag.length);
}

function sendToN8N(data) {
    fetch('/api/sync-n8n', { 
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => console.log('Sincronización exitosa:', result))
    .catch(error => console.error('Error en el puente:', error));
}

// EDITAR (Abre el mismo modal de antes pero con los datos)
function editTemplate(id, title, body) {
    $('#q-id').val(id);
    $('#q-title').val(title);
    $('#q-body').val(body);
    openConfigQuickMessages();
}

function syncContact() {
    const data = {
        name: currentContactName,  // Asegúrate de tener estas variables globales
        phone: currentContactPhone,
        body: "Sincronización de contacto desde Dashboard",
        path: 'sync-contact-whatsapp' // Esto es extra, pero sirve para debug
    };

    fetch('/api/sync-n8n', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => console.log('Respuesta:', result));
}