#!/bin/bash
set -e

# Aguardar o banco de dados estar disponível
echo "Aguardando banco de dados..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" --silent; do
    sleep 1
done
echo "Banco de dados disponível!"

# Verificar se o GLPI já foi instalado
if [ ! -f "/var/www/html/glpi/config/config_db.php" ]; then
    echo "Configurando GLPI pela primeira vez..."
    
    # Criar arquivo de configuração do banco
    cat > /var/www/html/glpi/config/config_db.php << EOF
<?php
class DB extends DBmysql {
   public \$dbhost = '${DB_HOST}';
   public \$dbuser = '${DB_USER}';
   public \$dbpassword = '${DB_PASSWORD}';
   public \$dbdefault = '${DB_NAME}';
}
EOF

    # Executar instalação do GLPI
    cd /var/www/html/glpi
    php bin/console glpi:database:install \
        --db-host="$DB_HOST" \
        --db-name="$DB_NAME" \
        --db-user="$DB_USER" \
        --db-password="$DB_PASSWORD" \
        --no-interaction
    
    # Criar usuário admin padrão
    php bin/console glpi:user:create \
        --username=admin \
        --password=admin \
        --email=admin@localhost \
        --no-interaction || true
        
    echo "GLPI instalado com sucesso!"
else
    echo "GLPI já está configurado."
fi

# Definir permissões corretas
chown -R www-data:www-data /var/www/html/glpi
chmod -R 755 /var/www/html/glpi

# Verificar se o plugin LAPS está instalado
echo "Verificando plugin LAPS..."
if [ -d "/var/www/html/glpi/plugins/lapsglpi" ]; then
    echo "Plugin LAPS encontrado. Instalando..."
    cd /var/www/html/glpi
    
    # Tentar instalar o plugin via CLI (se disponível)
    php bin/console glpi:plugin:install lapsglpi || echo "Instalação via CLI falhou, será necessário instalar manualmente"
else
    echo "Plugin LAPS não encontrado!"
fi

echo "Iniciando Apache..."
exec apache2-foreground