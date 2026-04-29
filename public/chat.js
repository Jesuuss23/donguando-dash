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
    // Mostrar botón de configuración de comandos
$('#btn-cmd-config').removeClass('hidden');
    
    updateButtonUI(isIntervened);
    fetchMessages(contactId);
    updateBotStatus(contactId);
    renderTags(contactId);
    loadContactInfo(contactId);
    
    // Marcar como leído
    $.post('/chat/mark-as-read/' + contactId);

    // Actualizar estado del botón anclar en el menú
$.get('/contact-info/' + contactId, function(contact) {
    if (contact) {
        updatePinButtonState(contact.is_pinned);
    }
});
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

// ========== FUNCIONES DE TAGS MEJORADAS ==========

function showTagModal() {
    if (!currentContactId) {
        alert("Primero selecciona un contacto");
        return;
    }
    
    // Cargar tags existentes primero
    $.get('/admin/tags', function(existingTags) {
        let tagOptions = '<option value="">-- Seleccionar o crear nuevo --</option>';
        tagOptions += '<option value="__new__">✨ Crear nueva etiqueta</option>';
        tagOptions += '<hr>';
        
        existingTags.forEach(tag => {
            tagOptions += `<option value="${tag.id}" style="color: ${tag.color}">🏷️ ${tag.name}</option>`;
        });
        
        // Crear un selector modal personalizado
        const tempDiv = $(`
            <div id="tag-selector-modal" class="fixed inset-0 z-[90] flex items-center justify-center bg-black bg-opacity-50">
                <div class="bg-white rounded-xl shadow-xl w-96 p-4">
                    <h3 class="font-bold text-lg mb-3">Agregar etiqueta a ${escapeHtml(currentContactName)}</h3>
                    <select id="tag-select" class="w-full border rounded-lg px-3 py-2 mb-3">
                        ${tagOptions}
                    </select>
                    <div id="new-tag-input" class="hidden">
                        <input type="text" id="custom-tag-name" placeholder="Nombre de la nueva etiqueta..." 
                               class="w-full border rounded-lg px-3 py-2 mb-2">
                        <div class="flex gap-2 mb-3">
                            <input type="color" id="custom-tag-color" value="#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT) . '" 
                                   class="w-12 h-10 rounded border">
                            <span class="text-xs text-gray-500 self-center">Elige un color</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="closeTagSelector()" class="flex-1 px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cancelar</button>
                        <button onclick="confirmAddTag()" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg font-bold">Agregar</button>
                    </div>
                    <div class="mt-3 text-center">
                        <button onclick="openTagsManager()" class="text-xs text-blue-500 hover:underline">⚙️ Administrar todas las etiquetas</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(tempDiv);
        
        // Evento para mostrar/ocultar input de nueva etiqueta
        $('#tag-select').on('change', function() {
            if ($(this).val() === '__new__') {
                $('#new-tag-input').removeClass('hidden');
            } else {
                $('#new-tag-input').addClass('hidden');
            }
        });
        
        // Guardar referencia
        window.selectedTagId = null;
    });
}

function closeTagSelector() {
    $('#tag-selector-modal').remove();
}

function confirmAddTag() {
    const selectedValue = $('#tag-select').val();
    
    if (selectedValue === '__new__') {
        // Crear nueva etiqueta
        const newTagName = $('#custom-tag-name').val().trim();
        if (!newTagName) {
            alert('Ingresa un nombre para la etiqueta');
            return;
        }
        
        $.ajax({
            url: '/admin/tags',
            method: 'POST',
            data: {
                name: newTagName,
                color: $('#custom-tag-color').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(newTag) {
                // Asignar la nueva etiqueta al contacto
                assignTagToContact(newTag.id);
            },
            error: function() {
                alert('Error al crear la etiqueta');
            }
        });
    } else if (selectedValue && selectedValue !== '') {
        // Usar etiqueta existente
        assignTagToContact(selectedValue);
    } else {
        alert('Selecciona una etiqueta');
    }
}

function assignTagToContact(tagId) {
    $.ajax({
        url: `/contacts/${currentContactId}/tags`,
        method: 'POST',
        data: {
            name: '', // Esto es para compatibilidad, el backend usará el tagId
            tag_id: tagId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            renderTags(currentContactId);
            loadTagFilters();
            closeTagSelector();
            showToast('✅ Etiqueta agregada', 'success');
        },
        error: function() {
            alert('Error al asignar la etiqueta');
        }
    });
}

function openTagsManager() {
    closeTagSelector();
    $('#modal-tags-manager').removeClass('hidden');
    loadTagsManager();
}

function closeTagsManager() {
    $('#modal-tags-manager').addClass('hidden');
}

function loadTagsManager() {
    $.get('/admin/tags', function(tags) {
        let container = $('#tags-list');
        container.empty();
        
        if (tags.length === 0) {
            container.html('<div class="text-center text-gray-400 py-8">No hay etiquetas creadas</div>');
            return;
        }
        
        tags.forEach(tag => {
            container.append(`
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2 border border-gray-200 w-full">
                    <div class="flex items-center gap-2 flex-1">
                        <span class="w-6 h-6 rounded-full border shadow-sm" style="background-color: ${tag.color}"></span>
                        <input type="text" class="tag-name-input font-bold text-gray-800 bg-transparent border-none focus:outline-none focus:ring-1 focus:ring-purple-500 rounded px-2 py-1" 
                               value="${escapeHtml(tag.name)}" data-original="${escapeHtml(tag.name)}" data-id="${tag.id}">
                        <input type="color" class="tag-color-input w-8 h-8 rounded border cursor-pointer" 
                               value="${tag.color}" data-id="${tag.id}">
                    </div>
                    <div class="flex gap-1">
                        <button onclick="saveTagEdit(${tag.id})" class="text-green-600 hover:text-green-800 px-2 py-1 rounded text-xs font-bold">💾 Guardar</button>
                        <button onclick="deleteTagFromManager(${tag.id}, '${escapeHtml(tag.name)}')" class="text-red-600 hover:text-red-800 px-2 py-1 rounded text-xs font-bold">🗑️ Eliminar</button>
                    </div>
                </div>
            `);
        });
    });
}

function saveTagEdit(tagId) {
    const $row = $(`input[data-id="${tagId}"]`).first();
    const $nameInput = $row.closest('.flex').find('.tag-name-input');
    const $colorInput = $row.closest('.flex').find('.tag-color-input');
    
    const newName = $nameInput.val().trim();
    const newColor = $colorInput.val();
    
    if (!newName) {
        alert('El nombre no puede estar vacío');
        return;
    }
    
    $.ajax({
        url: `/admin/tags/${tagId}`,
        method: 'PUT',
        data: {
            name: newName,
            color: newColor,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            loadTagsManager();
            loadTagFilters();
            if (currentContactId) {
                renderTags(currentContactId);
            }
            showToast('✅ Etiqueta actualizada', 'success');
        },
        error: function() {
            alert('Error al guardar');
        }
    });
}

function deleteTagFromManager(tagId, tagName) {
    if (!confirm(`¿Eliminar la etiqueta "${tagName}"? Se quitará de todos los contactos.`)) return;
    
    $.ajax({
        url: `/admin/tags/${tagId}`,
        method: 'DELETE',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function() {
            loadTagsManager();
            loadTagFilters();
            if (currentContactId) {
                renderTags(currentContactId);
            }
            showToast('🏷️ Etiqueta eliminada', 'success');
        },
        error: function() {
            alert('Error al eliminar');
        }
    });
}

function createTag() {
    const tagName = $('#new-tag-name').val().trim();
    const tagColor = $('#new-tag-color').val();
    
    if (!tagName) {
        alert('Ingresa un nombre para la etiqueta');
        return;
    }
    
    $.ajax({
        url: '/admin/tags',
        method: 'POST',
        data: {
            name: tagName,
            color: tagColor,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            $('#new-tag-name').val('');
            loadTagsManager();
            loadTagFilters();
            showToast('✅ Etiqueta creada', 'success');
        },
        error: function() {
            alert('Error al crear la etiqueta');
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
        
        if (products.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-gray-400 py-8">No hay productos registrados</td></tr>');
            return;
        }
        
        products.forEach(p => {
            // Formatear precio
            const precioFormateado = `S/ ${parseFloat(p.price).toFixed(2)}`;
            
            // Stock con colores
            let stockClass = '';
            let stockText = p.stock;
            if (p.stock <= 0) {
                stockClass = 'text-red-600 font-bold';
                stockText = 'AGOTADO';
            } else if (p.stock <= 5) {
                stockClass = 'text-orange-600 font-bold';
            } else {
                stockClass = 'text-green-600';
            }
            
            // Beneficio y Psicología (con valor por defecto)
            const beneficio = p.beneficio && p.beneficio !== '' ? p.beneficio : '—';
            const psicologia = p.psicologia_venta && p.psicologia_venta !== '' ? p.psicologia_venta : '—';
            
            tbody.append(`
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3 font-bold text-gray-800">${escapeHtml(p.name)}</td>
                    <td class="px-4 py-3 text-blue-600 font-black">${precioFormateado}</td>
                    <td class="px-4 py-3 ${stockClass} font-medium">${stockText}</td>
                    <td class="px-4 py-3 text-xs text-gray-600">${escapeHtml(beneficio)}</td>
                    <td class="px-4 py-3 text-xs text-gray-600">${escapeHtml(psicologia)}</td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="editProduct(${p.id})" class="text-[10px] bg-blue-100 text-blue-600 px-2 py-1 rounded hover:bg-blue-200">Editar</button>
                        <button onclick="deleteProduct(${p.id}, '${escapeHtml(p.name)}')" class="text-[10px] bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">Eliminar</button>
                    </td>
                </tr>
            `);
        });
    }).fail(function() {
        $('#inventory-table-body').html('<tr><td colspan="6" class="text-center text-red-500 py-8">Error al cargar productos</td></tr>');
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
        $('#p-beneficio').val(p.beneficio || '');
        $('#p-psicologia').val(p.psicologia_venta || '');
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
    // Inicializar detección de comandos
setupCommandDetection();
    loadOrderedContacts();
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
            beneficio: $('#p-beneficio').val(),
            psicologia_venta: $('#p-psicologia').val(),
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
    
// Refresh automático de mensajes Y lista de contactos (solo si NO hay filtro activo)
setInterval(() => {
    if (!document.hidden) {
        // Verificar si hay un filtro de búsqueda activo
        const searchQuery = $('#search-contacts').val();
        const isFilterActive = searchQuery && searchQuery.length > 0;
        
        // Si NO hay filtro activo, recargar la lista ordenada
        if (!isFilterActive && currentTagFilter === null) {
            loadOrderedContacts();
        }
        
        // Siempre refrescar mensajes del chat activo
        if (currentContactId) {
            fetchMessages(currentContactId);
            updateBotStatus(currentContactId);
        }
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
    // Guardar el filtro activo
    currentTagFilter = tagId;
    
    // Marcar botón activo
    $('#tag-filters-list button').removeClass('ring-2 ring-red-500 font-bold');
    if (tagId && tagId !== '') {
        $(`#tag-filters-list button[onclick="filterByTag(${tagId})"]`).addClass('ring-2 ring-red-500 font-bold');
    } else {
        $('#tag-filters-list button[onclick="filterByTag(\'\')"]').addClass('ring-2 ring-red-500 font-bold');
        currentTagFilter = null;
        loadOrderedContacts();
        return;
    }
    
    // Filtrar por tag
    $.get(`/contacts/by-tag/${tagId}`, function(contacts) {
        let html = '';
        
        if (contacts.length === 0) {
            html = '<div class="text-center text-gray-400 py-8">No hay contactos con esta etiqueta</div>';
        } else {
            contacts.forEach(contact => {
                const isIntervened = contact.is_intervened == 0;
                const statusClass = isIntervened ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600';
                const statusText = isIntervened ? 'AUTO' : 'MANUAL';
                
                html += `
                    <div onclick="loadChat(${contact.id}, '${escapeHtml(contact.name)}', ${isIntervened})"
                         class="p-4 border-b hover:bg-gray-50 cursor-pointer transition flex justify-between items-center contact-card">
                        <div>
                            <p class="font-bold text-gray-800">${escapeHtml(contact.name)}</p>
                            <p class="text-xs text-gray-500">${escapeHtml(contact.whatsapp_id)}</p>
                            ${isIntervened ? `<div class="text-[9px] text-green-600 mt-1">🤖 IA: ${contact.ia_messages_count ?? 0}</div>` : ''}
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${statusClass}">
                            ${statusText}
                        </span>
                    </div>
                `;
            });
        }
        
        $('#contact-list').html(html);
    });
}

// ========== CARGAR CONTACTOS ORDENADOS ==========
function loadOrderedContacts() {
    // Si hay un filtro de tag activo, NO recargar
    if (currentTagFilter !== null && currentTagFilter !== '') {
        console.log('Filtro de etiqueta activo, no se recarga automáticamente');
        return;
    }
    
    // Si hay búsqueda activa, NO recargar
    const searchQuery = $('#search-contacts').val();
    if (searchQuery && searchQuery.length > 0) {
        console.log('Búsqueda activa, no se recarga automáticamente');
        return;
    }
    
    $.get('/contacts/ordered', function(contacts) {
        let html = '';
        
        contacts.forEach(contact => {
            const isIntervened = contact.is_intervened == 0;
            const statusClass = isIntervened ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600';
            const statusText = isIntervened ? 'AUTO' : 'MANUAL';
            const isPinned = contact.is_pinned;
            
            html += `
                <div onclick="loadChat(${contact.id}, '${escapeHtml(contact.name)}', ${isIntervened})"
                     class="p-4 border-b hover:bg-gray-50 cursor-pointer transition flex justify-between items-center contact-card ${isPinned ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''}">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-bold text-gray-800 ${isPinned ? 'text-blue-700' : ''}">
                                ${escapeHtml(contact.name)}
                            </p>
                            ${isPinned ? '<span class="text-xs text-blue-500" title="Chat anclado">📌</span>' : ''}
                        </div>
                        <p class="text-xs text-gray-500">${escapeHtml(contact.whatsapp_id)}</p>
                        ${isIntervened ? `<div class="text-[9px] text-green-600 mt-1">🤖 IA: ${contact.ia_messages_count ?? 0}</div>` : ''}
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${statusClass}">
                            ${statusText}
                        </span>
                    </div>
                </div>
            `;
        });
        
        $('#contact-list').html(html);
    });
}

// ========== ACTUALIZAR BOTÓN ANCLAR EN MENÚ ==========
function updatePinButtonState(isPinned) {
    if (isPinned) {
        $('#pin-chat-text').text('Desanclar chat');
        $('#btn-pin-chat span:first-child').text('📍');
    } else {
        $('#pin-chat-text').text('Anclar chat');
        $('#btn-pin-chat span:first-child').text('📌');
    }
}

// ========== ANCLAR/DESANCLAR DESDE EL MENÚ ==========
function togglePinChatFromMenu() {
    if (!currentContactId) {
        showToast('Primero selecciona un chat', 'warning');
        return;
    }
    
    $.post('/chat/toggle-pin/' + currentContactId, function(data) {
        loadOrderedContacts(); // Recargar lista para mostrar/ocultar el pin
        updatePinButtonState(data.is_pinned);
        showToast(data.is_pinned ? '📌 Chat anclado' : '📍 Chat desanclado', 'success');
    }).fail(function() {
        showToast('Error al anclar/desanclar', 'error');
    });
}

// ========== EXPORTAR CONTACTOS ==========
function exportContacts() {
    window.location.href = '/export/contacts';
}

function exportFilteredContacts() {
    const filters = {
        ia_status: currentTagFilter ? 'filtered' : '',
        tag_id: currentTagFilter || ''
    };
    window.location.href = '/export/contacts/filtered?' + $.param(filters);
}

// ========== SISTEMA DE COMANDOS (INDEPENDIENTE) ==========

function openCmdConfig() {
    $('#modal-cmd-config').removeClass('hidden');
    loadCmdCategories();
    loadCmdCategorySelect();
    loadCmdCommands();
}

function closeCmdConfig() {
    $('#modal-cmd-config').addClass('hidden');
}

function showCmdTab(tab) {
    if (tab === 'categories') {
        $('#cmd-categories-panel').removeClass('hidden');
        $('#cmd-commands-panel').addClass('hidden');
        $('#cmd-tab-categories').addClass('border-b-2 border-green-500 text-green-600').removeClass('text-gray-500');
        $('#cmd-tab-commands').removeClass('border-b-2 border-green-500 text-green-600').addClass('text-gray-500');
        loadCmdCategories();
    } else {
        $('#cmd-categories-panel').addClass('hidden');
        $('#cmd-commands-panel').removeClass('hidden');
        $('#cmd-tab-commands').addClass('border-b-2 border-green-500 text-green-600').removeClass('text-gray-500');
        $('#cmd-tab-categories').removeClass('border-b-2 border-green-500 text-green-600').addClass('text-gray-500');
        loadCmdCommands();
    }
}

function loadCmdCategories() {
    $.get('/cmd/categories', function(categories) {
        console.log('📦 Categorías recibidas:', categories);
        
        if (!categories || categories.length === 0) {
            $('#cmd-categories-list').html('<div class="text-center text-gray-400 py-4">No hay categorías creadas</div>');
            return;
        }
        
        let html = '';
        categories.forEach(cat => {
            const commandsCount = cat.quick_commands ? cat.quick_commands.length : 0;
            html += `
                <div class="flex items-center justify-between bg-white border rounded-lg p-3 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">${cat.icon || '📁'}</span>
                        <div>
                            <div class="font-bold text-gray-800">${escapeHtml(cat.name)}</div>
                            <div class="text-xs text-gray-500">${commandsCount} comando${commandsCount !== 1 ? 's' : ''}</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editCmdCategory(${cat.id}, '${escapeHtml(cat.name)}', '${cat.icon || '📁'}')" 
                                class="text-blue-500 hover:text-blue-700 px-2 py-1 rounded text-sm transition-colors" title="Editar categoría">
                            ✏️ Editar
                        </button>
                        <button onclick="deleteCmdCategory(${cat.id})" 
                                class="text-red-500 hover:text-red-700 px-2 py-1 rounded text-sm transition-colors" title="Eliminar categoría">
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>
            `;
        });
        
        $('#cmd-categories-list').html(html);
    }).fail(function() {
        $('#cmd-categories-list').html('<div class="text-center text-red-500 py-4">Error al cargar categorías</div>');
    });
}

function createCmdCategory() {
    let name = $('#cmd-new-category-name').val().trim();
    let icon = $('#cmd-new-category-icon').val().trim();
    
    if (!name) {
        showToast('Ingresa un nombre para la categoría', 'warning');
        return;
    }
    
    $.ajax({
        url: '/cmd/categories',
        method: 'POST',
        data: {
            name: name,
            icon: icon,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            $('#cmd-new-category-name').val('');
            $('#cmd-new-category-icon').val('🥩');
            showToast('✅ Categoría creada', 'success');
            loadCmdCategories();
            loadCmdCategorySelect();
        },
        error: function() {
            showToast('Error al crear la categoría', 'error');
        }
    });
}

function editCmdCategory(id, currentName, currentIcon) {
    let newName = prompt('✏️ Editar nombre de la categoría:', currentName);
    if (!newName || newName === currentName) return;
    
    let newIcon = prompt('🎨 Editar icono (ej: 🥩, 🐷, 🐔):', currentIcon);
    if (!newIcon) newIcon = currentIcon;
    
    $.ajax({
        url: `/cmd/categories/${id}`,
        method: 'PUT',
        data: {
            name: newName,
            icon: newIcon,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            showToast('✅ Categoría actualizada', 'success');
            loadCmdCategories();
            loadCmdCategorySelect();
            loadQuickCommandsPanel();
        },
        error: function() {
            showToast('Error al actualizar la categoría', 'error');
        }
    });
}

function deleteCmdCategory(id) {
    // Obtener el nombre de la categoría primero para mostrarlo en el mensaje
    $.get(`/cmd/categories/${id}`, function(category) {
        const commandsCount = category.quick_commands ? category.quick_commands.length : 0;
        const warning = commandsCount > 0 
            ? `⚠️ ADVERTENCIA: Esta categoría tiene ${commandsCount} comando${commandsCount !== 1 ? 's' : ''}.\n\n¡También se eliminarán TODOS los comandos de esta categoría!\n\n`
            : '';
        
        if (!confirm(`${warning}¿Eliminar la categoría "${category.name}" permanentemente?`)) return;
        
        $.ajax({
            url: `/cmd/categories/${id}`,
            method: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                showToast('🗑️ Categoría eliminada', 'success');
                loadCmdCategories();
                loadCmdCategorySelect();
                loadCmdCommands();
                loadQuickCommandsPanel();
            },
            error: function() {
                showToast('Error al eliminar la categoría', 'error');
            }
        });
    });
}

function loadCmdCategorySelect() {
    $.get('/cmd/categories', function(categories) {
        let options = '<option value="">Seleccionar categoría</option>';
        categories.forEach(cat => {
            options += `<option value="${cat.id}">${cat.icon || '📁'} ${cat.name}</option>`;
        });
        $('#cmd-filter-category, #cmd-edit-category').html(options);
    });
}

function loadCmdCommands() {
    let categoryId = $('#cmd-filter-category').val();
    let url = categoryId ? `/cmd/commands?category=${categoryId}` : '/cmd/commands';
    
    $.get(url, function(commands) {
        let html = '<div class="space-y-2">';
        commands.forEach(cmd => {
            html += `
                <div class="flex items-center justify-between bg-white border rounded-lg p-3 hover:shadow-md">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            ${cmd.command ? `<span class="text-[10px] font-mono bg-gray-100 px-2 py-0.5 rounded-full text-green-600">${cmd.command}</span>` : ''}
                            <span class="font-bold text-gray-800">${escapeHtml(cmd.title)}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 truncate">${escapeHtml(cmd.body.substring(0, 80))}</div>
                        <div class="text-[9px] text-gray-400 mt-1">Categoría: ${cmd.category ? cmd.category.name : 'Sin categoría'}</div>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="editCmdCommand(${cmd.id})" class="text-blue-500 hover:text-blue-700 px-2 py-1 text-sm">✏️</button>
                        <button onclick="deleteCmdCommand(${cmd.id})" class="text-red-500 hover:text-red-700 px-2 py-1 text-sm">🗑️</button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        $('#cmd-commands-list').html(html || '<div class="text-center text-gray-400 py-4">No hay comandos creados</div>');
    });
}

function openCmdCommandForm() {
    $('#cmd-edit-id').val('');
    $('#cmd-command').val('');
    $('#cmd-title').val('');
    $('#cmd-body').val('');
    $('#cmd-edit-category').val('');
    $('#cmd-form-title').text('Nuevo Comando');
    $('#modal-cmd-command-form').removeClass('hidden');
}

function closeCmdCommandForm() {
    $('#modal-cmd-command-form').addClass('hidden');
}

function saveCmdCommand() {
    let data = {
        id: $('#cmd-edit-id').val(),
        category_id: $('#cmd-edit-category').val(),
        command: $('#cmd-command').val(),
        title: $('#cmd-title').val(),
        body: $('#cmd-body').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    if (!data.category_id) {
        showToast('Selecciona una categoría', 'warning');
        return;
    }
    if (!data.title || !data.body) {
        showToast('Título y mensaje son obligatorios', 'warning');
        return;
    }
    
    $.ajax({
        url: '/cmd/commands/save',
        method: 'POST',
        data: data,
        success: function() {
            showToast('✅ Comando guardado', 'success');
            closeCmdCommandForm();
            loadCmdCommands();
            loadQuickCommandsPanel();
        },
        error: function() {
            showToast('Error al guardar', 'error');
        }
    });
}

function deleteCmdCommand(id) {
    if (!confirm('¿Eliminar este comando?')) return;
    
    $.ajax({
        url: `/cmd/commands/delete/${id}`,
        method: 'DELETE',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function() {
            showToast('🗑️ Comando eliminado', 'success');
            loadCmdCommands();
            loadQuickCommandsPanel();
        }
    });
}

function editCmdCommand(id) {
    $.get(`/cmd/commands/${id}`, function(cmd) {
        $('#cmd-edit-id').val(cmd.id);
        $('#cmd-edit-category').val(cmd.category_id);
        $('#cmd-command').val(cmd.command);
        $('#cmd-title').val(cmd.title);
        $('#cmd-body').val(cmd.body);
        $('#cmd-form-title').text('Editar Comando');
        $('#modal-cmd-command-form').removeClass('hidden');
    });
}

function loadQuickRepliesPanel() {
    $.get('/cmd/commands', function(commands) {
        let commandsHtml = '';
        let categoriesHtml = {};
        
        commands.forEach(cmd => {
            if (cmd.command) {
                commandsHtml += `<button onclick="insertCommand('${cmd.command}')" class="text-[9px] px-2 py-1 rounded-full bg-gray-100 hover:bg-green-100 text-gray-600 transition-colors">${cmd.command}</button>`;
            }
            
            let catName = cmd.category ? cmd.category.name : 'General';
            let catIcon = cmd.category ? cmd.category.icon : '📁';
            if (!categoriesHtml[catName]) {
                categoriesHtml[catName] = { icon: catIcon, commands: [] };
            }
            categoriesHtml[catName].commands.push(cmd);
        });
        
        $('#quick-commands-list').html(commandsHtml || '<span class="text-[9px] text-gray-400">Sin comandos configurados</span>');
        
        let categoriesHtmlStr = '';
        for (let catName in categoriesHtml) {
            let cat = categoriesHtml[catName];
            categoriesHtmlStr += `
                <div class="p-2 border-b">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-sm">${cat.icon}</span>
                        <span class="text-[10px] font-bold text-gray-600 uppercase">${catName}</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
            `;
            cat.commands.forEach(cmd => {
                categoriesHtmlStr += `
                    <button onclick="sendQuickCommand('${escapeHtml(cmd.body)}')" 
                            class="text-[10px] px-3 py-1 rounded-full bg-white border border-gray-200 hover:border-green-400 hover:bg-green-50 transition-colors shadow-sm">
                        ${escapeHtml(cmd.title)}
                    </button>
                `;
            });
            categoriesHtmlStr += `</div></div>`;
        }
        
        $('#quick-replies-categories').html(categoriesHtmlStr || '<div class="text-center text-gray-400 text-xs py-4">No hay comandos configurados</div>');
    });
}

function sendQuickCommand(messageBody) {
    if (!currentContactPhone) {
        showToast('⚠️ Primero selecciona un chat', 'warning');
        return;
    }
    sendToN8N(messageBody);
}

function insertCommand(command) {
    $('#message-input').val(command + ' ');
    $('#message-input').focus();
    $('#quick-replies-panel').addClass('hidden');
}

function sendMessage() {
    const message = $('#message-input').val().trim();
    if (!message) return;
    if (!currentContactPhone) {
        showToast('⚠️ Primero selecciona un chat', 'warning');
        return;
    }
    sendToN8N(message);
    $('#message-input').val('');
    $('#quick-replies-panel').addClass('hidden');
}

function setupCommandDetection() {
    $('#message-input').on('input', function() {
        let value = $(this).val();
        if (value === '/') {
            $('#quick-replies-panel').removeClass('hidden');
            loadQuickCommandsPanel();
        } else if (value.startsWith('/')) {
            showCommandSuggestions(value);
        } else {
            $('#quick-replies-panel').addClass('hidden');
            $('#command-suggestions').addClass('hidden');
        }
    });
    
    $('#message-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
}

function showCommandSuggestions(query) {
    $.get('/cmd/commands', function(commands) {
        let matches = commands.filter(c => c.command && c.command.toLowerCase().startsWith(query.toLowerCase()));
        if (matches.length === 0) {
            $('#command-suggestions').addClass('hidden');
            return;
        }
        
        let html = '<div class="divide-y">';
        matches.forEach(m => {
            let preview = m.body.length > 60 ? m.body.substring(0, 60) + '...' : m.body;
            html += `
                <div onclick="selectCommand('${m.command}', '${escapeHtml(m.body)}')" class="p-2 hover:bg-gray-100 cursor-pointer transition-colors">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-green-600 text-sm font-bold">${m.command}</span>
                        <span class="text-xs text-gray-500">${escapeHtml(m.title)}</span>
                    </div>
                    <div class="text-[10px] text-gray-400 mt-1 truncate">${escapeHtml(preview)}</div>
                </div>
            `;
        });
        html += '</div>';
        
        $('#command-suggestions').removeClass('hidden').html(html);
    });
}
function setupCommandDetection() {
    $('#message-input').on('input', function() {
        let value = $(this).val();
        if (value === '/') {
            $('#quick-replies-panel').removeClass('hidden');
            $('#command-suggestions').addClass('hidden');
            loadQuickRepliesPanel();
        } else if (value.startsWith('/')) {
            $('#quick-replies-panel').addClass('hidden');
            showCommandSuggestions(value);
        } else {
            $('#quick-replies-panel').addClass('hidden');
            $('#command-suggestions').addClass('hidden');
        }
    });
    
    $('#message-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
}

function selectCommand(command, body) {
    // Cerrar sugerencias
    $('#command-suggestions').addClass('hidden');
    $('#quick-replies-panel').addClass('hidden');
    
    // Enviar el mensaje directamente
    sendQuickCommand(body);
}
function openCmdConfigAndClose() {
    // Cerrar el panel de respuestas rápidas
    $('#quick-replies-panel').addClass('hidden');
    $('#command-suggestions').addClass('hidden');
    
    // Abrir el modal de configuración
    openCmdConfig();
}
// ========== FUNCIONES GLOBALES (para onclick en HTML) ==========
window.openCmdConfigAndClose = openCmdConfigAndClose;
window.openCmdConfig = openCmdConfig;
window.closeCmdConfig = closeCmdConfig;
window.showCmdTab = showCmdTab;
window.createCmdCategory = createCmdCategory;
window.editCmdCategory = editCmdCategory;
window.deleteCmdCategory = deleteCmdCategory;
window.openCmdCommandForm = openCmdCommandForm;
window.closeCmdCommandForm = closeCmdCommandForm;
window.saveCmdCommand = saveCmdCommand;
window.deleteCmdCommand = deleteCmdCommand;
window.editCmdCommand = editCmdCommand;
window.sendQuickCommand = sendQuickCommand;
window.insertCommand = insertCommand;
window.sendMessage = sendMessage;
window.selectCommand = selectCommand;
window.exportContacts = exportContacts;
window.exportFilteredContacts = exportFilteredContacts;
window.togglePinChatFromMenu = togglePinChatFromMenu;
window.togglePinChat = togglePinChat;
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