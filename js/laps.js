/**
 * LAPS-GLPI Plugin - Enhanced JavaScript Functions
 * 
 * @author Rafael Cavalheri
 * @license GPL-2.0-or-later
 * @version 2.0.0
 */

// Namespace para o plugin LAPS
var LAPS = LAPS || {};

// Configuration
LAPS.config = {
    maxPasswordAge: 30, // days
    sessionTimeout: 300000, // 5 minutes in milliseconds
    maxRetries: 3,
    retryDelay: 1000, // milliseconds
    csrfToken: null,
    debug: false
};

// Security features
LAPS.security = {
    sessionStartTime: Date.now(),
    passwordViewCount: 0,
    maxPasswordViews: 10,
    lastActivity: Date.now()
};

/**
 * Get plugin base URL
 */
LAPS.getPluginUrl = function(path) {
    var baseUrl = window.location.protocol + '//' + window.location.host;
    var currentPath = window.location.pathname;
    var glpiRoot = '';
    
    // Detect GLPI root based on current URL
    if (currentPath.includes('/plugins/')) {
        glpiRoot = baseUrl + currentPath.split('/plugins/')[0];
    } else if (currentPath.includes('/front/')) {
        glpiRoot = baseUrl + currentPath.split('/front/')[0];
    } else {
        glpiRoot = baseUrl;
    }
    
    var pluginUrl = glpiRoot + '/plugins/lapsglpi/front/' + (path || 'computer.form.php');
    if (LAPS.config.debug) {
        console.log('LAPS Plugin URL:', pluginUrl);
    }
    return pluginUrl;
};

/**
 * Initialize CSRF token
 */
LAPS.initCSRF = function() {
    var csrfInput = document.querySelector('input[name="_glpi_csrf_token"]');
    if (csrfInput) {
        LAPS.config.csrfToken = csrfInput.value;
    }
};

/**
 * Validate session and security limits
 */
LAPS.validateSession = function() {
    var now = Date.now();
    
    // Check session timeout
    if (now - LAPS.security.sessionStartTime > LAPS.config.sessionTimeout) {
        LAPS.showMessage('Session expired. Please refresh the page.', 'error');
        return false;
    }
    
    // Check password view limit
    if (LAPS.security.passwordViewCount >= LAPS.security.maxPasswordViews) {
        LAPS.showMessage('Maximum password views reached for this session.', 'warning');
        return false;
    }
    
    // Update last activity
    LAPS.security.lastActivity = now;
    return true;
};

/**
 * Sanitize input to prevent XSS
 */
LAPS.sanitizeInput = function(input) {
    if (typeof input !== 'string') return input;
    
    var div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
};

/**
 * Validate form inputs
 */
LAPS.validateForm = function(formData) {
    var errors = [];
    
    if (formData.laps_server_url) {
        if (!LAPS.isValidUrl(formData.laps_server_url)) {
            errors.push('Invalid server URL format');
        }
    }
    
    if (formData.connection_timeout) {
        var timeout = parseInt(formData.connection_timeout);
        if (isNaN(timeout) || timeout < 5 || timeout > 300) {
            errors.push('Connection timeout must be between 5 and 300 seconds');
        }
    }
    
    if (formData.cache_duration) {
        var duration = parseInt(formData.cache_duration);
        if (isNaN(duration) || duration < 60 || duration > 3600) {
            errors.push('Cache duration must be between 60 and 3600 seconds');
        }
    }
    
    return errors;
};

/**
 * Plugin initialization
 */
LAPS.init = function() {
    try {
        // Initialize CSRF token
        LAPS.initCSRF();
        
        // Initialize tooltips
        LAPS.initTooltips();
        
        // Bind events
        LAPS.bindEvents();
        
        // Initialize form validation
        LAPS.initFormValidation();
        
        // Start session monitoring
        LAPS.startSessionMonitoring();
        
        // Initialize keyboard shortcuts
        LAPS.initKeyboardShortcuts();
        
        if (LAPS.config.debug) {
            console.log('LAPS Plugin initialized successfully');
        }
    } catch (error) {
        console.error('LAPS Plugin initialization failed:', error);
        LAPS.showMessage('Plugin initialization failed', 'error');
    }
};

/**
 * Initialize form validation
 */
LAPS.initFormValidation = function() {
    var forms = document.querySelectorAll('form[name="form"]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var formData = new FormData(form);
            var data = {};
            
            for (var pair of formData.entries()) {
                data[pair[0]] = pair[1];
            }
            
            var errors = LAPS.validateForm(data);
            if (errors.length > 0) {
                e.preventDefault();
                LAPS.showMessage('Validation errors: ' + errors.join(', '), 'error');
            }
        });
    });
};

/**
 * Start session monitoring
 */
LAPS.startSessionMonitoring = function() {
    setInterval(function() {
        var now = Date.now();
        var timeSinceLastActivity = now - LAPS.security.lastActivity;
        
        // Warn user about session timeout
        if (timeSinceLastActivity > LAPS.config.sessionTimeout - 60000) { // 1 minute warning
            LAPS.showMessage('Session will expire soon. Please refresh the page.', 'warning');
        }
    }, 30000); // Check every 30 seconds
};

/**
 * Initialize keyboard shortcuts
 */
LAPS.initKeyboardShortcuts = function() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+Alt+P: Show password (if available)
        if (e.ctrlKey && e.altKey && e.key === 'p') {
            e.preventDefault();
            var showBtn = document.querySelector('.laps-show-password');
            if (showBtn && !showBtn.disabled) {
                showBtn.click();
            }
        }
        
        // Escape: Hide password
        if (e.key === 'Escape') {
            LAPS.hidePasswordResult();
        }
    });
};

/**
 * Show LAPS password with security validation
 */
LAPS.showPassword = function(elementId) {
    // Validate session first
    if (!LAPS.validateSession()) {
        return;
    }
    var hiddenElement = document.getElementById(elementId + '-hidden');
    var visibleElement = document.getElementById(elementId + '-visible');
    
    if (hiddenElement && visibleElement) {
        hiddenElement.style.display = 'none';
        visibleElement.style.display = 'inline-block';
        visibleElement.classList.add('laps-fade-in');
        
        // Log da ação
        LAPS.logAction('password_view', 'Password viewed');
    }
};

/**
 * Ocultar senha LAPS
 */
LAPS.hidePassword = function(elementId) {
    var hiddenElement = document.getElementById(elementId + '-hidden');
    var visibleElement = document.getElementById(elementId + '-visible');
    
    if (hiddenElement && visibleElement) {
        hiddenElement.style.display = 'inline-block';
        visibleElement.style.display = 'none';
        hiddenElement.classList.add('laps-fade-in');
    }
};

/**
 * Get LAPS password from server with enhanced security
 */
LAPS.getLapsPassword = function(computerId, computerName) {
    // Validate session and security limits
    if (!LAPS.validateSession()) {
        return;
    }
    
    // Increment password view count
    LAPS.security.passwordViewCount++;
    
    // Sanitize inputs
    computerId = LAPS.sanitizeInput(computerId);
    computerName = LAPS.sanitizeInput(computerName);
    
    console.log('LAPS.getLapsPassword called with:', computerId, computerName);
    
    var btn = document.getElementById('laps-show-password-btn');
    var loading = document.getElementById('laps-loading');
    var result = document.getElementById('laps-password-result');
    
    console.log('Button element:', btn);
    console.log('Loading element:', loading);
    console.log('Result element:', result);
    
    if (!btn || !loading || !result) {
        console.error('LAPS: Required elements not found');
        alert('Erro: Elementos necessários não encontrados na página.');
        return;
    }
    
    btn.style.display = 'none';
    loading.style.display = 'block';
    result.style.display = 'none';
    
    var url = LAPS.getPluginUrl();
    console.log('Making request to:', url);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        console.log('XHR state changed:', xhr.readyState, xhr.status);
        
        if (xhr.readyState === 4) {
            loading.style.display = 'none';
            
            console.log('Response status:', xhr.status);
            console.log('Response text:', xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    console.log('Parsed response:', response);
                    
                    if (response.success) {
                        result.innerHTML = '<div style="background: #f0f0f0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
                            '<strong>Password:</strong> <code style="font-family: monospace; font-size: 14px; background: #fff; padding: 2px 4px; border: 1px solid #ccc;">' + response.data.password + '</code>' +
                            '<br><br><button type="button" onclick="copyPassword(\'' + response.data.password + '\')" class="btn btn-info">Copy Password</button> ' +
                            '<button type="button" onclick="hidePassword()" class="btn btn-secondary">Hide</button>' +
                            '</div>';
                        result.style.display = 'block';
                        
                        // Log da ação
                        LAPS.logAction('password_view', 'Password viewed for computer: ' + computerName);
                        
                    } else {
                        result.innerHTML = '<div style="color: red; padding: 10px;">' + response.message + '</div>';
                        result.style.display = 'block';
                        btn.style.display = 'inline-block';
                    }
                } catch (e) {
                    console.error('LAPS: Error parsing response', e);
                    result.innerHTML = '<div style="color: red; padding: 10px;">Error processing response</div>';
                    result.style.display = 'block';
                    btn.style.display = 'inline-block';
                }
            } else {
                result.innerHTML = '<div style="color: red; padding: 10px;">Connection error</div>';
                result.style.display = 'block';
                btn.style.display = 'inline-block';
            }
        }
    };
    
    var params = 'get_password=1&computers_id=' + computerId + '&computer_name=' + encodeURIComponent(computerName);
    xhr.send(params);
    
    // Registrar ação
    LAPS.logAction('view_password', computerId);
};

/**
 * Ocultar resultado da senha
 */
LAPS.hidePasswordResult = function() {
    var btn = document.getElementById('laps-show-password-btn');
    var result = document.getElementById('laps-password-result');
    
    if (result) result.style.display = 'none';
    if (btn) btn.style.display = 'inline-block';
};

/**
 * Copiar senha para área de transferência
 */
LAPS.copyPassword = function(password, elementId) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(password).then(function() {
            LAPS.showMessage('Password copied to clipboard!', 'success');
            LAPS.logAction('password_copy', 'Password copied to clipboard');
        }).catch(function(err) {
            console.error('Error copying password: ', err);
            LAPS.fallbackCopyPassword(password);
        });
    } else {
        LAPS.fallbackCopyPassword(password);
    }
};

/**
 * Método alternativo para copiar senha (navegadores antigos)
 */
LAPS.fallbackCopyPassword = function(password) {
    var textArea = document.createElement('textarea');
    textArea.value = password;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            LAPS.showMessage('Password copied to clipboard!', 'success');
            LAPS.logAction('password_copy', 'Password copied to clipboard (fallback)');
        } else {
            LAPS.showMessage('Failed to copy password', 'error');
        }
    } catch (err) {
        console.error('Fallback copy failed: ', err);
        LAPS.showMessage('Copy not supported in this browser', 'warning');
    }
    
    document.body.removeChild(textArea);
};

/**
 * Sincronizar senha manualmente
 */
LAPS.syncPassword = function(computerId, computerName) {
    if (!confirm('Are you sure you want to force synchronization for this computer?')) {
        return;
    }
    
    LAPS.showLoading('Synchronizing password...');
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = LAPS.getPluginUrl();
    
    var computerIdInput = document.createElement('input');
    computerIdInput.type = 'hidden';
    computerIdInput.name = 'computers_id';
    computerIdInput.value = computerId;
    form.appendChild(computerIdInput);
    
    var computerNameInput = document.createElement('input');
    computerNameInput.type = 'hidden';
    computerNameInput.name = 'computer_name';
    computerNameInput.value = computerName;
    form.appendChild(computerNameInput);
    
    var syncInput = document.createElement('input');
    syncInput.type = 'hidden';
    syncInput.name = 'sync_password';
    syncInput.value = '1';
    form.appendChild(syncInput);
    
    document.body.appendChild(form);
    form.submit();
};

/**
 * Limpar cache de senha
 */
LAPS.clearCache = function(computerId, computerName) {
    if (!confirm('Are you sure you want to clear the password cache for this computer?')) {
        return;
    }
    
    LAPS.showLoading('Clearing cache...');
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = LAPS.getPluginUrl();
    
    var computerIdInput = document.createElement('input');
    computerIdInput.type = 'hidden';
    computerIdInput.name = 'computers_id';
    computerIdInput.value = computerId;
    form.appendChild(computerIdInput);
    
    var computerNameInput = document.createElement('input');
    computerNameInput.type = 'hidden';
    computerNameInput.name = 'computer_name';
    computerNameInput.value = computerName;
    form.appendChild(computerNameInput);
    
    var clearInput = document.createElement('input');
    clearInput.type = 'hidden';
    clearInput.name = 'clear_cache';
    clearInput.value = '1';
    form.appendChild(clearInput);
    
    document.body.appendChild(form);
    form.submit();
};

/**
 * Mostrar logs do computador
 */
LAPS.showLogs = function(computerId) {
    var url = LAPS.getPluginUrl() + '?show_logs=1&computers_id=' + computerId;
    window.open(url, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
};

/**
 * Testar conexão com servidor LAPS
 */
LAPS.testConnection = function() {
    LAPS.showLoading('Testing connection...');
    
    var form = document.querySelector('form[name="form"]');
    if (form) {
        var testInput = document.createElement('input');
        testInput.type = 'hidden';
        testInput.name = 'test_connection';
        testInput.value = '1';
        form.appendChild(testInput);
        form.submit();
    }
};

/**
 * Testar conexão via AJAX
 */
LAPS.testConnectionAjax = function() {
    var resultElement = document.getElementById('test-connection-result');
    var loadingElement = document.getElementById('test-connection-loading');
    var buttonElement = document.getElementById('test-connection-btn');
    
    if (!resultElement || !loadingElement || !buttonElement) {
        console.error('Test connection elements not found');
        return;
    }
    
    // Mostrar loading
    loadingElement.style.display = 'block';
    resultElement.innerHTML = '';
    buttonElement.disabled = true;
    
    // Obter valores do formulário
    var serverUrl = document.querySelector('input[name="laps_server_url"]').value;
    var apiKey = document.querySelector('input[name="laps_api_key"]').value;
    var timeout = document.querySelector('input[name="connection_timeout"]').value;
    
    // Se a API key for o placeholder, usar a chave atual
    if (apiKey === '••••••••••••••••') {
        // Tentar obter a chave atual do servidor
        apiKey = '';
    }
    
    // Fazer requisição AJAX
    var xhr = new XMLHttpRequest();
    // Usar endpoint específico para AJAX - URL absoluta baseada na localização atual
    var baseUrl = window.location.protocol + '//' + window.location.host;
    var currentPath = window.location.pathname;
    var glpiRoot = '';
    
    // Detectar raiz do GLPI baseado na URL atual
    if (currentPath.includes('/plugins/')) {
        glpiRoot = baseUrl + currentPath.split('/plugins/')[0];
    } else if (currentPath.includes('/front/')) {
        glpiRoot = baseUrl + currentPath.split('/front/')[0];
    } else {
        glpiRoot = baseUrl + '/glpi';
    }
    
    var ajaxUrl = glpiRoot + '/plugins/lapsglpi/ajax/simple_test_connection.php';
    xhr.open('POST', ajaxUrl, true);
    xhr.withCredentials = true; // Incluir cookies de sessão
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // Identificar como requisição AJAX
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            loadingElement.style.display = 'none';
            buttonElement.disabled = false;
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        var message = '✓ ' + response.message;
                        if (response.data && response.data.version) {
                            message += '<br><small>Server Version: ' + response.data.version + '</small>';
                        }
                        if (response.data && response.data.status) {
                            message += '<br><small>Status: ' + response.data.status + '</small>';
                        }
                        resultElement.innerHTML = '<span class="connection-success">' + message + '</span>';
                    } else {
                        resultElement.innerHTML = '<span class="connection-error">✗ ' + response.message + '</span>';
                    }
                } catch (e) {
                    console.log('Response is not JSON:', xhr.responseText);
                    console.log('Parse error:', e);
                    
                    // Verificar se a resposta contém HTML de erro do GLPI
                    if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('Parse error')) {
                        resultElement.innerHTML = '<span class="connection-error">✗ Server configuration error. Check GLPI logs.</span>';
                    } else if (xhr.responseText.includes('Connection successful')) {
                        resultElement.innerHTML = '<span class="connection-success">✓ Connection successful</span>';
                    } else if (xhr.responseText.includes('Connection error')) {
                        resultElement.innerHTML = '<span class="connection-error">✗ Connection error</span>';
                    } else if (xhr.responseText.includes('Invalid API Key')) {
                        resultElement.innerHTML = '<span class="connection-error">✗ Invalid API Key</span>';
                    } else if (xhr.responseText.includes('HTTP error')) {
                        var httpMatch = xhr.responseText.match(/HTTP error: (\d+)/);
                        var httpCode = httpMatch ? httpMatch[1] : 'unknown';
                        resultElement.innerHTML = '<span class="connection-error">✗ HTTP Error ' + httpCode + '</span>';
                    } else if (xhr.responseText.includes('Invalid JSON response')) {
                        resultElement.innerHTML = '<span class="connection-error">✗ Invalid server response</span>';
                    } else if (xhr.responseText.includes('LAPS Server URL and API Key are required')) {
                        resultElement.innerHTML = '<span class="connection-error">✗ Server URL and API Key are required</span>';
                    } else if (xhr.responseText.trim() === '') {
                        resultElement.innerHTML = '<span class="connection-error">✗ Empty response from server. Check server URL and connectivity.</span>';
                    } else {
                        // Tentar extrair mensagem de erro mais específica
                        var errorMatch = xhr.responseText.match(/<span class="[^"]*error[^"]*">([^<]+)<\/span>/);
                        if (errorMatch) {
                            resultElement.innerHTML = '<span class="connection-error">✗ ' + errorMatch[1] + '</span>';
                        } else {
                            // Mostrar uma prévia da resposta para debug
                            var preview = xhr.responseText.substring(0, 100).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            resultElement.innerHTML = '<span class="connection-warning">⚠ Unexpected response format.<br><small>Response preview: ' + preview + '...</small><br><small>Check server configuration and GLPI logs.</small></span>';
                        }
                    }
                }
            } else if (xhr.status === 0) {
                resultElement.innerHTML = '<span class="connection-error">✗ Network error. Check server URL and connectivity.</span>';
            } else {
                resultElement.innerHTML = '<span class="connection-error">✗ Request failed (HTTP ' + xhr.status + ')</span>';
            }
        }
    };
    
    // Enviar dados
    var postData = 'server_url=' + encodeURIComponent(serverUrl) + 
                   '&api_key=' + encodeURIComponent(apiKey) + 
                   '&timeout=' + encodeURIComponent(timeout);
    
    xhr.send(postData);
}

/**
 * Mostrar mensagem para o usuário
 */
LAPS.showMessage = function(message, type) {
    type = type || 'info';
    
    // Remover mensagens existentes
    var existingMessages = document.querySelectorAll('.laps-message');
    existingMessages.forEach(function(msg) {
        msg.remove();
    });
    
    var messageDiv = document.createElement('div');
    messageDiv.className = 'laps-message ' + type + ' laps-fade-in';
    messageDiv.textContent = message;
    
    // Inserir no topo da página
    var container = document.querySelector('.center') || document.body;
    container.insertBefore(messageDiv, container.firstChild);
    
    // Remover automaticamente após 5 segundos
    setTimeout(function() {
        if (messageDiv.parentNode) {
            messageDiv.parentNode.removeChild(messageDiv);
        }
    }, 5000);
};

/**
 * Mostrar indicador de carregamento
 */
LAPS.showLoading = function(message) {
    message = message || 'Loading...';
    
    var loadingDiv = document.createElement('div');
    loadingDiv.id = 'laps-loading';
    loadingDiv.className = 'laps-message info laps-fade-in';
    loadingDiv.innerHTML = '<span class="laps-loading"></span>' + message;
    
    var container = document.querySelector('.center') || document.body;
    container.insertBefore(loadingDiv, container.firstChild);
};

/**
 * Ocultar indicador de carregamento
 */
LAPS.hideLoading = function() {
    var loadingDiv = document.getElementById('laps-loading');
    if (loadingDiv) {
        loadingDiv.remove();
    }
};

/**
 * Inicializar tooltips
 */
LAPS.initTooltips = function() {
    var tooltips = document.querySelectorAll('.laps-tooltip');
    tooltips.forEach(function(tooltip) {
        tooltip.addEventListener('mouseenter', function() {
            var tooltipText = this.querySelector('.laps-tooltiptext');
            if (tooltipText) {
                tooltipText.style.visibility = 'visible';
                tooltipText.style.opacity = '1';
            }
        });
        
        tooltip.addEventListener('mouseleave', function() {
            var tooltipText = this.querySelector('.laps-tooltiptext');
            if (tooltipText) {
                tooltipText.style.visibility = 'hidden';
                tooltipText.style.opacity = '0';
            }
        });
    });
};

/**
 * Vincular eventos
 */
LAPS.bindEvents = function() {
    // Evento para formulários de configuração
    var configForm = document.querySelector('form[name="form"]');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            var serverUrl = document.querySelector('input[name="laps_server_url"]');
            if (serverUrl && !serverUrl.value.trim()) {
                e.preventDefault();
                LAPS.showMessage('LAPS Server URL is required', 'error');
                serverUrl.focus();
                return false;
            }
        });
    }
    
    // Evento para validação de URL
    var urlInput = document.querySelector('input[name="laps_server_url"]');
    if (urlInput) {
        urlInput.addEventListener('blur', function() {
            var url = this.value.trim();
            if (url && !LAPS.isValidUrl(url)) {
                this.classList.add('error');
                LAPS.showMessage('Please enter a valid URL', 'warning');
            } else {
                this.classList.remove('error');
            }
        });
    }
};

/**
 * Validar URL
 */
LAPS.isValidUrl = function(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
};

/**
 * Registrar ação no console (para debug)
 */
LAPS.logAction = function(action, details) {
    if (console && console.log) {
        console.log('LAPS Action:', action, details);
    }
};

/**
 * Formatar data/hora
 */
LAPS.formatDateTime = function(dateString) {
    if (!dateString) return 'Never';
    
    var date = new Date(dateString);
    return date.toLocaleString();
};

/**
 * Verificar se senha está expirada
 */
LAPS.isPasswordExpired = function(expiryDate) {
    if (!expiryDate) return false;
    
    var expiry = new Date(expiryDate);
    var now = new Date();
    
    return expiry < now;
};

/**
 * Calcular tempo até expiração
 */
LAPS.getTimeUntilExpiry = function(expiryDate) {
    if (!expiryDate) return null;
    
    var expiry = new Date(expiryDate);
    var now = new Date();
    var diff = expiry - now;
    
    if (diff <= 0) return 'Expired';
    
    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
    var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    
    if (days > 0) {
        return days + ' day' + (days > 1 ? 's' : '') + ', ' + hours + ' hour' + (hours > 1 ? 's' : '');
    } else {
        return hours + ' hour' + (hours > 1 ? 's' : '');
    }
};

/**
 * Atualizar status em tempo real
 */
LAPS.updateStatus = function() {
    var statusElements = document.querySelectorAll('[data-laps-expiry]');
    statusElements.forEach(function(element) {
        var expiryDate = element.getAttribute('data-laps-expiry');
        var timeUntilExpiry = LAPS.getTimeUntilExpiry(expiryDate);
        
        if (timeUntilExpiry) {
            element.textContent = timeUntilExpiry;
            
            if (timeUntilExpiry === 'Expired') {
                element.className = 'laps-status error';
            } else if (timeUntilExpiry.includes('hour')) {
                element.className = 'laps-status warning';
            } else {
                element.className = 'laps-status success';
            }
        }
    });
};

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', LAPS.init);
} else {
    LAPS.init();
}

// Atualizar status a cada minuto
setInterval(LAPS.updateStatus, 60000);

// Funções globais para compatibilidade com código inline
function showLapsPassword(elementId) {
    LAPS.showPassword(elementId || 'laps-password');
}

function hideLapsPassword(elementId) {
    LAPS.hidePassword(elementId || 'laps-password');
}

function copyLapsPassword(password) {
    LAPS.copyPassword(password);
}

function syncLapsPassword(computerId, computerName) {
    LAPS.syncPassword(computerId, computerName);
}

function clearLapsCache(computerId, computerName) {
    LAPS.clearCache(computerId, computerName);
}

function showLapsLogs(computerId) {
    LAPS.showLogs(computerId);
}

function testLapsConnection() {
    LAPS.testConnection();
}

function testLapsConnectionAjax() {
    LAPS.testConnectionAjax();
}

function getLapsPassword(computer_id, computer_name) {
    return LAPS.getLapsPassword(computer_id, computer_name);
}

function hidePassword() {
    return LAPS.hidePasswordResult();
}

function copyPassword(password) {
    return LAPS.copyPassword(password);
}