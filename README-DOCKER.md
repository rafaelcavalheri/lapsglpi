# Plugin LAPS-GLPI - Ambiente Docker para Testes

Este ambiente Docker permite testar o plugin LAPS-GLPI em um ambiente isolado e controlado.

## Pré-requisitos

- Docker
- Docker Compose

## Como executar

### 1. Construir e iniciar os containers

```bash
docker-compose up --build
```

### 2. Aguardar a inicialização

O processo pode levar alguns minutos para:
- Baixar e instalar o GLPI
- Configurar o banco de dados
- Instalar o plugin LAPS

### 3. Acessar o GLPI

- **URL**: http://localhost:8080
- **Usuário**: admin
- **Senha**: admin

### 4. Servidor LAPS simulado

- **URL**: http://localhost:8081/api.php
- **API Key**: `5deeb8a3-e591-4bd4-8bfb-f9d8b117844c`

## Configuração do Plugin

1. Acesse o GLPI como administrador
2. Vá em **Configuração > Plugins**
3. Encontre o plugin "LAPS-GLPI" e clique em **Instalar**
4. Após a instalação, clique em **Ativar**
5. Vá em **Configuração > LAPS** para configurar:
   - **URL do Servidor LAPS**: `http://laps-server/api.php`
   - **Chave da API**: `5deeb8a3-e591-4bd4-8bfb-f9d8b117844c`
   - **Timeout**: 30 segundos
   - **Cache Duration**: 300 segundos

## Testando o Plugin

1. Vá em **Ativos > Computadores**
2. Você verá computadores de teste pré-criados:
   - DESKTOP-TEST01
   - LAPTOP-TEST02
   - SERVER-TEST03
3. Clique em qualquer computador
4. Você deve ver uma aba **LAPS Password**
5. Clique na aba para visualizar as informações de senha LAPS

## Logs e Debugging

### Ver logs dos containers
```bash
# Logs do GLPI
docker-compose logs glpi

# Logs do MySQL
docker-compose logs mysql

# Logs do servidor LAPS simulado
docker-compose logs laps-server
```

### Acessar container do GLPI
```bash
docker exec -it lapsglpi-web bash
```

### Verificar logs do GLPI
```bash
# Dentro do container
tail -f /var/www/html/glpi/files/_log/php-errors.log
tail -f /var/www/html/glpi/files/_log/sql-errors.log
```

## Estrutura dos Containers

- **mysql**: Banco de dados MySQL 8.0
- **glpi**: GLPI 10.0.10 com Apache e PHP 8.1
- **laps-server**: Servidor LAPS simulado para testes

## Volumes Persistentes

- `mysql_data`: Dados do banco MySQL
- `glpi_files`: Arquivos do GLPI
- `glpi_config`: Configurações do GLPI

## Parar o ambiente

```bash
docker-compose down
```

## Limpar completamente (remover volumes)

```bash
docker-compose down -v
docker system prune -f
```

## Troubleshooting

### Plugin não aparece
1. Verifique se os arquivos estão no diretório correto
2. Verifique as permissões dos arquivos
3. Consulte os logs do Apache/PHP

### Erro de conexão com LAPS
1. Verifique se o servidor LAPS está rodando: http://localhost:8081/api.php
2. Teste a API manualmente:
   ```bash
   curl -X POST http://localhost:8081/api.php \
        -d "action=status&api_key=5deeb8a3-e591-4bd4-8bfb-f9d8b117844c"
   ```

### Banco de dados não inicializa
1. Verifique os logs do MySQL
2. Aguarde mais tempo para a inicialização
3. Reinicie os containers se necessário

## Desenvolvimento

Para desenvolvimento ativo do plugin:

1. Modifique os arquivos localmente
2. Os arquivos são montados como volume no container
3. Reinicie o container GLPI para aplicar mudanças:
   ```bash
   docker-compose restart glpi
   ```