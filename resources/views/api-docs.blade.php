<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Documentación API</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .nav-button {
            padding: 10px 20px;
            background: white;
            border: 2px solid #667eea;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
        }

        .nav-button:hover {
            background: #667eea;
            color: white;
        }

        .nav-button.active {
            background: #667eea;
            color: white;
        }

        .endpoint-group {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .endpoint-group h2 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.8em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        .endpoint-group-desc {
            color: #666;
            margin-bottom: 20px;
            font-style: italic;
        }

        .endpoint {
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .method {
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9em;
            color: white;
        }

        .method.GET { background: #61affe; }
        .method.POST { background: #49cc90; }
        .method.PUT { background: #fca130; }
        .method.DELETE { background: #f93e3e; }

        .endpoint-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
        }

        .endpoint-path {
            background: #2d3748;
            color: #68d391;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
        }

        .section {
            margin: 15px 0;
        }

        .section h4 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .param-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .param-table th {
            background: #667eea;
            color: white;
            padding: 10px;
            text-align: left;
        }

        .param-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .param-table tr:hover {
            background: #f5f5f5;
        }

        .required {
            color: #f93e3e;
            font-weight: bold;
        }

        .optional {
            color: #999;
        }

        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            margin: 10px 0;
        }

        .response-example {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .note {
            background: #fef5e7;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin: 10px 0;
            border-radius: 3px;
        }

        .note ul {
            margin-left: 20px;
        }

        .note li {
            margin: 5px 0;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge.auth {
            background: #fca130;
            color: white;
        }

        .badge.no-auth {
            background: #68d391;
            color: white;
        }

        .quick-links {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 250px;
        }

        .quick-links h3 {
            color: #667eea;
            margin-bottom: 15px;
        }

        .quick-links a {
            display: block;
            padding: 8px 12px;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            margin-bottom: 5px;
        }

        .quick-links a:hover {
            background: #667eea;
            color: white;
        }

        @media (max-width: 768px) {
            .quick-links {
                position: static;
                margin-bottom: 20px;
            }

            .endpoint-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .footer {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            margin-top: 40px;
        }

        .search-box {
            width: 100%;
            padding: 15px;
            border: 2px solid #667eea;
            border-radius: 5px;
            font-size: 1em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 {{ config('app.name') }} - API</h1>
            <p>Documentación completa de endpoints disponibles</p>
            <p style="margin-top: 10px; font-size: 0.9em;">
                <strong>Base URL:</strong> <code>{{ url('/api') }}</code>
            </p>
        </div>

        <input type="text" id="searchBox" class="search-box" placeholder="🔍 Buscar endpoint...">

        <div class="nav-buttons" id="navButtons">
            <a href="#" class="nav-button active" data-group="all">📋 Todos</a>
        </div>

        <div id="endpointsContainer">
            <!-- Los endpoints se cargarán dinámicamente aquí -->
        </div>

        <div class="footer">
            <p>{{ config('app.name') }} API v1.0.0</p>
            <p style="margin-top: 10px;">Última actualización: {{ date('d/m/Y') }}</p>
        </div>
    </div>

    <script>
        // Cargar la documentación desde el endpoint
        let allEndpoints = [];
        let currentGroup = 'all';

        async function loadDocumentation() {
            try {
                const response = await fetch('{{ url("/api/docs") }}');
                const data = await response.json();
                allEndpoints = data.endpoints;
                
                // Crear botones de navegación
                createNavigationButtons();
                
                // Mostrar todos los endpoints
                displayEndpoints(allEndpoints);
            } catch (error) {
                console.error('Error cargando documentación:', error);
                document.getElementById('endpointsContainer').innerHTML = 
                    '<div class="note">❌ Error cargando la documentación. Por favor, recarga la página.</div>';
            }
        }

        function createNavigationButtons() {
            const groups = [...new Set(allEndpoints.map(e => e.group))];
            const navButtons = document.getElementById('navButtons');
            
            groups.forEach(group => {
                const count = allEndpoints.filter(e => e.group === group).length;
                const button = document.createElement('a');
                button.href = '#';
                button.className = 'nav-button';
                button.dataset.group = group;
                button.textContent = `${getGroupIcon(group)} ${capitalizeFirst(group)} (${count})`;
                button.onclick = (e) => {
                    e.preventDefault();
                    filterByGroup(group);
                };
                navButtons.appendChild(button);
            });
        }

        function getGroupIcon(group) {
            const icons = {
                'cliente': '👥',
                'mercadopago': '💳',
                'whatsapp': '📱',
                'documentation': '📖'
            };
            return icons[group] || '📌';
        }

        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function filterByGroup(group) {
            currentGroup = group;
            
            // Actualizar botones activos
            document.querySelectorAll('.nav-button').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.group === group) {
                    btn.classList.add('active');
                }
            });
            
            // Filtrar endpoints
            const filtered = group === 'all' 
                ? allEndpoints 
                : allEndpoints.filter(e => e.group === group);
            
            displayEndpoints(filtered);
        }

        function displayEndpoints(endpoints) {
            const container = document.getElementById('endpointsContainer');
            const groupedEndpoints = {};
            
            // Agrupar endpoints
            endpoints.forEach(endpoint => {
                if (!groupedEndpoints[endpoint.group]) {
                    groupedEndpoints[endpoint.group] = [];
                }
                groupedEndpoints[endpoint.group].push(endpoint);
            });
            
            // Generar HTML
            let html = '';
            Object.keys(groupedEndpoints).forEach(group => {
                const groupEndpoints = groupedEndpoints[group];
                const groupDesc = groupEndpoints[0].group_description || '';
                
                html += `
                    <div class="endpoint-group" id="group-${group}">
                        <h2>${getGroupIcon(group)} ${capitalizeFirst(group)}</h2>
                        <div class="endpoint-group-desc">${groupDesc}</div>
                `;
                
                groupEndpoints.forEach(endpoint => {
                    html += generateEndpointHTML(endpoint);
                });
                
                html += '</div>';
            });
            
            container.innerHTML = html;
        }

        function generateEndpointHTML(endpoint) {
            let html = `
                <div class="endpoint">
                    <div class="endpoint-header">
                        <span class="method ${endpoint.method}">${endpoint.method}</span>
                        <span class="endpoint-name">${endpoint.name}</span>
                        <span class="badge ${endpoint.authentication ? 'auth' : 'no-auth'}">
                            ${endpoint.authentication ? '🔒 Requiere Auth' : '🔓 Público'}
                        </span>
                    </div>
                    
                    <div class="endpoint-path">${endpoint.endpoint}</div>
                    
                    <div class="section">
                        <p>${endpoint.description}</p>
                    </div>
            `;
            
            // Parámetros
            if (endpoint.parameters) {
                Object.keys(endpoint.parameters).forEach(paramType => {
                    const params = endpoint.parameters[paramType];
                    if (params && params.length > 0) {
                        html += `
                            <div class="section">
                                <h4>📝 Parámetros (${paramType})</h4>
                                <table class="param-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Requerido</th>
                                            <th>Descripción</th>
                                            <th>Ejemplo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        params.forEach(param => {
                            html += `
                                <tr>
                                    <td><strong>${param.name}</strong></td>
                                    <td><code>${param.type}</code></td>
                                    <td class="${param.required ? 'required' : 'optional'}">
                                        ${param.required ? '✓ Sí' : '✗ No'}
                                    </td>
                                    <td>${param.description}</td>
                                    <td><code>${JSON.stringify(param.example)}</code></td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                });
            }
            
            // Ejemplo de Request
            if (endpoint.request_example) {
                html += `
                    <div class="section">
                        <h4>📤 Ejemplo de Request</h4>
                `;
                
                if (endpoint.request_example.curl) {
                    html += `
                        <div class="code-block">${escapeHtml(endpoint.request_example.curl)}</div>
                    `;
                }
                
                if (endpoint.request_example.json) {
                    html += `
                        <div class="code-block">${escapeHtml(JSON.stringify(endpoint.request_example.json, null, 2))}</div>
                    `;
                }
                
                html += '</div>';
            }
            
            // Respuesta de éxito
            if (endpoint.response_success) {
                html += `
                    <div class="section">
                        <h4>✅ Respuesta Exitosa (${endpoint.response_success.code})</h4>
                        <div class="code-block">${escapeHtml(JSON.stringify(endpoint.response_success.example, null, 2))}</div>
                    </div>
                `;
            }
            
            // Respuesta de error
            if (endpoint.response_error) {
                html += `
                    <div class="section">
                        <h4>❌ Respuesta de Error (${endpoint.response_error.code})</h4>
                        <div class="code-block">${escapeHtml(JSON.stringify(endpoint.response_error.example, null, 2))}</div>
                    </div>
                `;
            }
            
            // Notas
            if (endpoint.notes && endpoint.notes.length > 0) {
                html += `
                    <div class="note">
                        <h4>⚠️ Notas Importantes</h4>
                        <ul>
                `;
                
                endpoint.notes.forEach(note => {
                    html += `<li>${note}</li>`;
                });
                
                html += `
                        </ul>
                    </div>
                `;
            }
            
            html += '</div>';
            return html;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Búsqueda
        document.getElementById('searchBox').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            if (searchTerm === '') {
                filterByGroup(currentGroup);
                return;
            }
            
            const filtered = allEndpoints.filter(endpoint => {
                return endpoint.name.toLowerCase().includes(searchTerm) ||
                       endpoint.endpoint.toLowerCase().includes(searchTerm) ||
                       endpoint.description.toLowerCase().includes(searchTerm) ||
                       endpoint.group.toLowerCase().includes(searchTerm);
            });
            
            displayEndpoints(filtered);
        });

        // Cargar documentación al inicio
        loadDocumentation();
    </script>
</body>
</html>
