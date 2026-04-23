// ============================================
// CHAT.JS - VERSIÓN DEFINITIVA
// Carnicería Don Guando
// ============================================

// ========== VARIABLES GLOBALES ==========
let currentContactId = null;      // ID numérico de la BD
let currentContactPhone = null;   // Número de teléfono REAL (519xxxx)
let currentContactName = null;    // Nombre del contacto
let isFetching = false;
let currentSelectedPrice = 0;

// ========== FUNCIONES AUXILIARES ==========
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
function formatLinks(text) {
    if (!text) return '---';
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    return text.replace(urlRegex, function(url) {
        return `<a href="${url}" target="_blank" class="text-blue-600 underline hover:text-blue-800">${url}</a>`;
    });
}

function showToast(message, type = 'info') {
    if ($('#toast-container').length === 0) {
        $('body').append('<div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>');
    }
    
    const colors = { success: 'bg-green-500', error: 'bg-red-500', info: 'bg-blue-500', warning: 'bg-yellow-500' };
    const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    const toastId = 'toast-' + Date.now();
    
    const toast = $(`
        <div id="${toastId}" class="${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 animate-slide-in">
            <span>${icons[type]}</span>
            <span>${message}</span>
            <button onclick="$('#${toastId}').remove()" class="ml-2 hover:opacity-75">×</button>
        </div>
    `);
    
    $('#toast-container').append(toast);
    setTimeout(() => toast.fadeOut(300, () => toast.remove()), 3000);
}

// ========== FUNCIONES DE INTERVENCIÓN IA ==========
function updateButtonUI(isIntervened) {
    const btn = $('#btn-intervene');
    btn.removeClass('hidden bg-green-500 bg-red-600');
    
    if (isIntervened) {
        btn.addClass('bg-red-600 text-white').text('MANUAL (IA OFF)');
    } else {
        btn.addClass('bg-green-500 text-white').text('AUTO (IA ON)');
    }
}

function updateBotStatus(contactId) {
    $.get('/check-status-by-id/' + contactId, function(data) {
        updateButtonUI(data.is_intervened);
    });
}

function toggleIntervention() {
    if (!currentContactId) return;
    
    const btn = $('#btn-intervene');
    btn.prop('disabled', true);
    
    $.post('/chat/intervene/' + currentContactId, function(data) {
        updateButtonUI(data.is_intervened);
        
        const contactCard = $(`.contact-card[onclick*="loadChat(${currentContactId}"]`);
        const statusSpan = contactCard.find('span:last-child');
        
        if (data.is_intervened) {
            statusSpan.removeClass('bg-green-100 text-green-600').addClass('bg-red-100 text-red-600').text('MANUAL');
        } else {
            statusSpan.removeClass('bg-red-100 text-red-600').addClass('bg-green-100 text-green-600').text('AUTO');
        }
        
        btn.prop('disabled', false);
        showToast(data.is_intervened ? '🔴 IA Desactivada' : '🟢 IA Activada', 'success');
    }).fail(function() {
        btn.prop('disabled', false);
        showToast('Error al cambiar estado', 'error');
    });
}

// ========== FUNCIONES DE CHAT ==========
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

function loadContactInfo(contactId) {
    $.ajax({
        url: '/contact-info/' + contactId,
        type: 'GET',
        cache: false,
        success: function(contact) {
            if (contact) {
                $('#info-producto').text(contact.producto || '---');
                $('#info-cantidad').text(contact.cantidad || '---');
                $('#info-direccion').html(formatLinks(contact.direccion || '---'));
                
                if (!contact.producto && !contact.cantidad && !contact.direccion) {
                    $('#order-panel').addClass('hidden');
                } else {
                    $('#order-panel').removeClass('hidden');
                }
            }
        }
    });
}

function loadChat(contactId, name, isIntervened) {
    // Guardar ID numérico
    currentContactId = contactId;
    currentContactName = name;
    
    // Capturar número de teléfono REAL desde el HTML
    const contactCard = $(`.contact-card[onclick*="loadChat(${contactId},"]`);
    const phoneElement = contactCard.find('p.text-xs');
    const realPhone = phoneElement.length ? phoneElement.text().trim() : null;
    
    if (realPhone && realPhone.startsWith('519')) {
        currentContactPhone = realPhone;
        console.log("✅ Teléfono capturado:", currentContactPhone);
    } else {
        currentContactPhone = null;
        console.warn("⚠️ No se pudo capturar el teléfono para:", name);
    }
    
    // UI Inicial
    $('#btn-show-order').removeClass('hidden');
    $('#contact-name-header').text('Chat con ' + name);
    $('#chat-messages').html('<p class="text-center text-gray-500">Cargando...</p>');
    $('#btn-intervene, #btn-menu, #btn-add-tag').removeClass('hidden');
    
    updateButtonUI(isIntervened);
    fetchMessages(contactId);
    updateBotStatus(contactId);
    renderTags(contactId);
    loadContactInfo(contactId);
    
    // Marcar como leído
    $.post('/chat/mark-as-read/' + contactId);
}

// ========== FUNCIONES DE TAGS ==========
function renderTags(contactId) {
    if (!contactId) return;
    
    $.get(`/contacts/${contactId}/tags`, function(tags) {
        let container = $('#contact-tags-container');
        container.empty();
        
        if (Array.isArray(tags) && tags.length > 0) {
            tags.forEach(tag => {
                container.append(`
                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full border shadow-sm" 
                          style="background-color: ${tag.color}20; color: ${tag.color}; border-color: ${tag.color}">
                        ${tag.name}
                        <button onclick="removeTag(${contactId}, ${tag.id})" class="ml-1 hover:text-black font-bold" title="Eliminar etiqueta">×</button>
                    </span>
                `);
            });
        }
        loadTagFilters();
    }).fail(xhr => console.error("Error al obtener etiquetas:", xhr.responseText));
}

function showTagModal() {
    if (!currentContactId) {
        alert("Primero selecciona un contacto");
        return;
    }
    
    let tagName = prompt("Nombre de la etiqueta:");
    if (!tagName) return;
    
    $.ajax({
        url: `/contacts/${currentContactId}/tags`,
        method: 'POST',
        data: { name: tagName, _token: $('meta[name="csrf-token"]').attr('content') },
        success: function() {
            renderTags(currentContactId);
            showToast('✅ Etiqueta agregada', 'success');
        },
        error: function() {
            alert("Error al guardar la etiqueta");
        }
    });
}

function removeTag(contactId, tagId) {
    if (!confirm("¿Quitar esta etiqueta?")) return;
    
    $.ajax({
        url: `/contacts/${contactId}/tags/${tagId}`,
        method: 'DELETE',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function() {
            renderTags(contactId);
            showToast('🏷️ Etiqueta eliminada', 'success');
        },
        error: function() {
            alert("No se pudo eliminar la etiqueta");
        }
    });
}

// ========== FUNCIONES DE INVENTARIO ==========
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
                        <button onclick="editProduct(${p.id})" class="text-[10px] bg-blue-100 text-blue-600 px-2 py-1 rounded">Editar</button>
                        <button onclick="deleteProduct(${p.id}, '${p.name}')" class="text-[10px] bg-red-100 text-red-600 px-2 py-1 rounded">Eliminar</button>
                    </td>
                </tr>
            `);
        });
    });
}

function openInventory() {
    $('#modal-inventory').removeClass('hidden');
    loadInventoryData();
}

function closeInventory() {
    $('#modal-inventory').addClass('hidden');
    $('#inventory-search').val('');
}

function openProductForm() {
    $('#modal-product-form').removeClass('hidden');
}

function closeProductForm() {
    $('#modal-product-form').addClass('hidden');
    $('#product-form')[0].reset();
    $('#p-id').val('');
    $('#modal-product-form h3').text('🛒 Nuevo Producto');
}

function editProduct(id) {
    $('#modal-product-form h3').text('📝 Editar Producto');
    $.get(`/inventory/product/${id}`, function(p) {
        $('#p-id').val(p.id);
        $('#p-name').val(p.name);
        $('#p-price').val(p.price);
        $('#p-stock').val(p.stock);
        $('#p-unit').val(p.unit);
        $('#modal-product-form').removeClass('hidden');
    });
}

function deleteProduct(id, name) {
    if (confirm(`¿Eliminar "${name}"?`)) {
        $.ajax({
            url: `/inventory/delete/${id}`,
            method: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                alert("Producto eliminado");
                loadInventoryData();
            }
        });
    }
}

// ========== FUNCIONES DE MENSAJES RÁPIDOS ==========
function loadQuickMessages(productName, productPrice) {
    $.get('/quick-responses', function(responses) {
        let list = $('#quick-messages-list');
        list.empty();
        
        responses.forEach(res => {
            let msgFinal = res.body
                .replace(/{producto}/g, productName)
                .replace(/{precio}/g, productPrice);
            
            list.append(`
                <div class="mb-3 p-3 bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="text-[10px] font-black text-blue-500 uppercase mb-1">${res.title}</div>
                    <div class="text-xs text-gray-600 mb-3">${msgFinal}</div>
                    <button onclick="sendToN8N('${msgFinal.replace(/'/g, "\\'")}')" 
                            class="w-full bg-green-500 hover:bg-green-600 text-white text-[10px] font-bold py-1 px-2 rounded-lg">
                        🚀 Enviar Mensaje
                    </button>
                </div>
            `);
        });
    });
}

function selectProductForMessage(name, price) {
    currentSelectedPrice = price;
    $('#quick-messages-area').removeClass('hidden');
    $('#selected-product-name').text(name);
    loadQuickMessages(name, price);
}

// ========== ENVÍO A N8N (CORREGIDO) ==========
function sendToN8N(messageContent) {
    // Usar el número de teléfono REAL capturado
    const phoneNumber = currentContactPhone;
    
    console.log("📤 Enviando mensaje - Teléfono:", phoneNumber);
    console.log("📤 Mensaje:", messageContent);
    
    if (!phoneNumber || phoneNumber === 'null' || phoneNumber === 'undefined') {
        showToast("⚠️ No se pudo obtener el número del contacto. Selecciona el chat nuevamente.", 'warning');
        return;
    }
    
    // Enviar a través del proxy de Laravel (sin CORS)
    $.ajax({
        url: '/api/sync-n8n',
        method: 'POST',
        data: JSON.stringify({
            name: currentContactName,
            phone: phoneNumber,
            body: messageContent
        }),
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log("✅ Respuesta del servidor:", response);
            showToast('🚀 ¡Mensaje enviado exitosamente!', 'success');
        },
        error: function(xhr) {
            console.error("❌ Error al enviar:", xhr);
            showToast('❌ Error al enviar el mensaje', 'error');
        }
    });
}

// ========== FUNCIONES DE ACCIONES DEL CHAT ==========
function toggleOrderPanel() {
    $('#order-panel').toggleClass('hidden');
}

function clearOrderInfo() {
    if (!currentContactId) return;
    if (confirm('¿Limpiar los datos del pedido?')) {
        $.post('/chat/clear-order/' + currentContactId, function() {
            $('#info-producto, #info-cantidad, #info-direccion').text('---');
            $('#order-panel').addClass('hidden');
            alert('Ficha limpiada');
        });
    }
}

function toggleMenu() {
    $('#dropdown-menu').toggleClass('hidden');
}

function clearChat() {
    if (!currentContactId) return;
    if (confirm('¿Borrar todos los mensajes de este chat?')) {
        $.ajax({
            url: '/chat/clear/' + currentContactId,
            type: 'DELETE',
            success: function() {
                $('#chat-messages').html('<p class="text-center text-gray-500 mt-10">Chat vaciado</p>');
                $('#dropdown-menu').addClass('hidden');
                showToast('Chat vaciado', 'success');
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

// ========== FUNCIONES DE PLANTILLAS ==========
function openConfigQuickMessages() {
    $('#modal-quick-config').removeClass('hidden');
}

function closeConfigQuickMessages() {
    $('#modal-quick-config').addClass('hidden');
    $('#quick-response-form')[0].reset();
    $('#q-id').val('');
}

function insertTag(tag) {
    const textarea = document.getElementById('q-body');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const newText = text.substring(0, start) + tag + text.substring(end);
    textarea.value = newText;
    textarea.focus();
    textarea.setSelectionRange(start + tag.length, start + tag.length);
}

function deleteTemplate(id) {
    if (confirm("¿Borrar esta plantilla?")) {
        $.ajax({
            url: `/quick-responses/delete/${id}`,
            method: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                let currentP = $('#selected-product-name').text();
                if (currentP && currentP !== "") {
                    loadQuickMessages(currentP, currentSelectedPrice);
                }
                showToast('Plantilla eliminada', 'success');
            }
        });
    }
}

function editTemplate(id, title, body) {
    $('#q-id').val(id);
    $('#q-title').val(title);
    $('#q-body').val(body);
    openConfigQuickMessages();
}

function insertIntoChat(text) {
    $('#message-input').val(text).focus();
}

// ========== INICIALIZACIÓN ==========
$(document).ready(function() {
    console.log("🚀 Don Guando Dashboard iniciado");
    loadTagFilters();
    // Buscador de contactos
$('#search-contacts').on('input', function() {
    const query = $(this).val();
    searchContacts(query);
});
    // Buscador de inventario
    $('#inventory-search').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $("#inventory-table-body tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Búsqueda de productos en chat
    $('#chat-product-search').on('input', function() {
        let query = $(this).val();
        if (query.length < 2) {
            $('#chat-product-results').empty();
            return;
        }
        
        $.get(`/inventory/products?search=${query}`, function(products) {
            let results = $('#chat-product-results');
            results.empty();
            
            products.forEach(p => {
                results.append(`
                    <div onclick="selectProductForMessage('${p.name}', '${p.price}')" 
                         class="p-2 border rounded-lg hover:bg-red-50 cursor-pointer">
                        <div class="text-xs font-bold">${p.name}</div>
                        <div class="text-[10px] text-blue-600">S/ ${p.price} - Stock: ${p.stock}</div>
                    </div>
                `);
            });
        });
    });
    
    // Formulario de producto
    $('#product-form').on('submit', function(e) {
        e.preventDefault();
        
        let id = $('#p-id').val();
        let url = id ? `/inventory/update/${id}` : '/inventory/save';
        let formData = {
            name: $('#p-name').val(),
            price: $('#p-price').val(),
            stock: $('#p-stock').val(),
            unit: $('#p-unit').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.post(url, formData)
            .done(function(data) {
                let msg = id ? `¡Cambios guardados en ${data.name}!` : `¡Producto creado: ${data.name}!`;
                alert(msg);
                closeProductForm();
                loadInventoryData();
            })
            .fail(function() {
                alert("Error al procesar la solicitud");
            });
    });
    
    // Formulario de respuestas rápidas
    $(document).on('submit', '#quick-response-form', function(e) {
        e.preventDefault();
        
        const data = {
            id: $('#q-id').val(),
            title: $('#q-title').val(),
            body: $('#q-body').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        if (!data.title || !data.body) {
            alert("Por favor rellena todos los campos");
            return;
        }
        
        $.ajax({
            url: '/quick-responses/save',
            method: 'POST',
            data: data,
            success: function() {
                alert("¡Guardado correctamente!");
                closeConfigQuickMessages();
                
                let currentP = $('#selected-product-name').text();
                if (currentP && currentP !== "") {
                    loadQuickMessages(currentP, currentSelectedPrice);
                }
            },
            error: function() {
                alert("Error al guardar");
            }
        });
    });
    
    // Refresh automático de mensajes
    setInterval(() => {
        if (currentContactId) {
            fetchMessages(currentContactId);
            updateBotStatus(currentContactId);
        }
    }, 3000);
    
    // Cerrar dropdown al hacer clic fuera
    $(document).click(function(event) {
        if (!$(event.target).closest('#btn-menu, #dropdown-menu, #btn-show-order').length) {
            $('#dropdown-menu').addClass('hidden');
        }
    });
});
// ========== BÚSQUEDA DE CONTACTOS ==========
function searchContacts(query) {
    if (query.length === 0) {
        // Si no hay búsqueda, recargar todos los contactos
        location.reload();
        return;
    }
    
    $.get('/contacts/search?q=' + encodeURIComponent(query), function(contacts) {
        let html = '';
        
        contacts.forEach(contact => {
            html += `
                <div onclick="loadChat(${contact.id}, '${contact.name}', ${contact.is_intervened})"
                     class="p-4 border-b hover:bg-gray-50 cursor-pointer transition flex justify-between items-center contact-card">
                    <div>
                        <p class="font-bold text-gray-800">${escapeHtml(contact.name)}</p>
                        <p class="text-xs text-gray-500">${escapeHtml(contact.whatsapp_id)}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${contact.is_intervened ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'}">
                        ${contact.is_intervened ? 'MANUAL' : 'AUTO'}
                    </span>
                </div>
            `;
        });
        
        if (contacts.length === 0) {
            html = '<div class="text-center text-gray-400 py-8">No se encontraron contactos</div>';
        }
        
        $('#contact-list').html(html);
    });
}

// ========== FUNCIONES DE FILTROS POR TAG ==========
let currentTagFilter = null;

function loadTagFilters() {
    $.get('/tags/all', function(tags) {
        if (tags.length === 0) {
            $('#tag-filters-container').addClass('hidden');
            return;
        }
        
        $('#tag-filters-container').removeClass('hidden');
        let filtersHtml = '<button onclick="filterByTag(\'\')" class="text-[9px] px-2 py-1 rounded-full bg-gray-200 hover:bg-gray-300 transition-all">Todos</button>';
        
        tags.forEach(tag => {
            filtersHtml += `
                <button onclick="filterByTag(${tag.id})" 
                        class="text-[9px] px-2 py-1 rounded-full transition-all"
                        style="background-color: ${tag.color}20; color: ${tag.color}; border: 1px solid ${tag.color}">
                    ${escapeHtml(tag.name)}
                </button>
            `;
        });
        
        $('#tag-filters-list').html(filtersHtml);
    });
}

function filterByTag(tagId) {
    currentTagFilter = tagId;
    
    // Marcar botón activo
    $('#tag-filters-list button').removeClass('ring-2 ring-red-500');
    if (tagId) {
        $(`#tag-filters-list button[onclick="filterByTag(${tagId})"]`).addClass('ring-2 ring-red-500');
    } else {
        $('#tag-filters-list button[onclick="filterByTag(\'\')"]').addClass('ring-2 ring-red-500');
    }
    
    if (!tagId || tagId === '') {
        // Mostrar todos los contactos
        location.reload();
        return;
    }
    
    // Filtrar por tag
    $.get(`/contacts/by-tag/${tagId}`, function(contacts) {
        let html = '';
        
        if (contacts.length === 0) {
            html = '<div class="text-center text-gray-400 py-8">No hay contactos con esta etiqueta</div>';
        } else {
            contacts.forEach(contact => {
                html += `
                    <div onclick="loadChat(${contact.id}, '${escapeHtml(contact.name)}', ${contact.is_intervened})"
                         class="p-4 border-b hover:bg-gray-50 cursor-pointer transition flex justify-between items-center contact-card">
                        <div>
                            <p class="font-bold text-gray-800">${escapeHtml(contact.name)}</p>
                            <p class="text-xs text-gray-500">${escapeHtml(contact.whatsapp_id)}</p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${contact.is_intervened ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'}">
                            ${contact.is_intervened ? 'MANUAL' : 'AUTO'}
                        </span>
                    </div>
                `;
            });
        }
        
        $('#contact-list').html(html);
    });
}



// ========== FUNCIONES GLOBALES (para onclick en HTML) ==========
window.loadChat = loadChat;
window.toggleIntervention = toggleIntervention;
window.updateButtonUI = updateButtonUI;
window.showTagModal = showTagModal;
window.removeTag = removeTag;
window.openInventory = openInventory;
window.closeInventory = closeInventory;
window.openProductForm = openProductForm;
window.closeProductForm = closeProductForm;
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
window.selectProductForMessage = selectProductForMessage;
window.sendToN8N = sendToN8N;
window.openConfigQuickMessages = openConfigQuickMessages;
window.closeConfigQuickMessages = closeConfigQuickMessages;
window.deleteTemplate = deleteTemplate;
window.editTemplate = editTemplate;
window.insertTag = insertTag;
window.insertIntoChat = insertIntoChat;
window.clearChat = clearChat;
window.deleteContact = deleteContact;
window.clearOrderInfo = clearOrderInfo;
window.toggleOrderPanel = toggleOrderPanel;
window.toggleMenu = toggleMenu;