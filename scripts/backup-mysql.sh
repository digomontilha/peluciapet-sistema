#!/bin/bash

# =====================================================
# Script de Backup Automático - Sistema PelúciaPet
# Versão: 2.0.0
# Descrição: Backup completo do banco MySQL e arquivos
# =====================================================

# Configurações (edite conforme necessário)
DB_HOST="peluciapet.mysql.dbaas.com.br"
DB_NAME="peluciapet"
DB_USER="peluciapet"
DB_PASS="Ogid@102290"

# Diretórios
BACKUP_DIR="/home/backup/peluciapet"
WEB_DIR="/var/www/html"
LOG_FILE="/var/log/peluciapet-backup.log"

# Configurações de retenção
RETENTION_DAYS=30
MAX_BACKUPS=50

# Configurações de notificação
NOTIFY_EMAIL="admin@peluciapet.com.br"
WEBHOOK_URL=""  # URL para notificações via webhook (opcional)

# =====================================================
# Funções Auxiliares
# =====================================================

# Função de log
log_message() {
    local level=$1
    local message=$2
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [$level] $message" | tee -a "$LOG_FILE"
}

# Função para enviar notificações
send_notification() {
    local subject=$1
    local message=$2
    local status=$3
    
    # Log da notificação
    log_message "INFO" "Enviando notificação: $subject"
    
    # Email (se configurado)
    if [[ -n "$NOTIFY_EMAIL" ]] && command -v mail >/dev/null 2>&1; then
        echo "$message" | mail -s "$subject" "$NOTIFY_EMAIL"
    fi
    
    # Webhook (se configurado)
    if [[ -n "$WEBHOOK_URL" ]] && command -v curl >/dev/null 2>&1; then
        curl -X POST "$WEBHOOK_URL" \
             -H "Content-Type: application/json" \
             -d "{\"subject\":\"$subject\",\"message\":\"$message\",\"status\":\"$status\"}" \
             >/dev/null 2>&1
    fi
}

# Função para verificar dependências
check_dependencies() {
    local missing_deps=()
    
    # Verificar mysqldump
    if ! command -v mysqldump >/dev/null 2>&1; then
        missing_deps+=("mysqldump")
    fi
    
    # Verificar gzip
    if ! command -v gzip >/dev/null 2>&1; then
        missing_deps+=("gzip")
    fi
    
    # Verificar tar
    if ! command -v tar >/dev/null 2>&1; then
        missing_deps+=("tar")
    fi
    
    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        log_message "ERROR" "Dependências faltando: ${missing_deps[*]}"
        return 1
    fi
    
    return 0
}

# Função para criar diretórios
create_directories() {
    local dirs=("$BACKUP_DIR" "$BACKUP_DIR/database" "$BACKUP_DIR/files" "$BACKUP_DIR/logs")
    
    for dir in "${dirs[@]}"; do
        if [[ ! -d "$dir" ]]; then
            mkdir -p "$dir"
            if [[ $? -eq 0 ]]; then
                log_message "INFO" "Diretório criado: $dir"
            else
                log_message "ERROR" "Falha ao criar diretório: $dir"
                return 1
            fi
        fi
    done
    
    return 0
}

# Função para testar conexão com banco
test_database_connection() {
    log_message "INFO" "Testando conexão com banco de dados..."
    
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" "$DB_NAME" >/dev/null 2>&1
    
    if [[ $? -eq 0 ]]; then
        log_message "INFO" "Conexão com banco de dados OK"
        return 0
    else
        log_message "ERROR" "Falha na conexão com banco de dados"
        return 1
    fi
}

# =====================================================
# Funções de Backup
# =====================================================

# Backup do banco de dados
backup_database() {
    local timestamp=$(date '+%Y%m%d_%H%M%S')
    local backup_file="$BACKUP_DIR/database/peluciapet_db_$timestamp.sql"
    local compressed_file="$backup_file.gz"
    
    log_message "INFO" "Iniciando backup do banco de dados..."
    
    # Executar mysqldump
    mysqldump -h "$DB_HOST" \
              -u "$DB_USER" \
              -p"$DB_PASS" \
              --single-transaction \
              --routines \
              --triggers \
              --events \
              --add-drop-database \
              --add-drop-table \
              --create-options \
              --disable-keys \
              --extended-insert \
              --quick \
              --lock-tables=false \
              --set-charset \
              --comments \
              "$DB_NAME" > "$backup_file"
    
    if [[ $? -eq 0 ]]; then
        # Comprimir arquivo
        gzip "$backup_file"
        
        if [[ $? -eq 0 ]]; then
            local file_size=$(du -h "$compressed_file" | cut -f1)
            log_message "INFO" "Backup do banco concluído: $compressed_file ($file_size)"
            echo "$compressed_file"
            return 0
        else
            log_message "ERROR" "Falha ao comprimir backup do banco"
            rm -f "$backup_file"
            return 1
        fi
    else
        log_message "ERROR" "Falha no backup do banco de dados"
        rm -f "$backup_file"
        return 1
    fi
}

# Backup dos arquivos
backup_files() {
    local timestamp=$(date '+%Y%m%d_%H%M%S')
    local backup_file="$BACKUP_DIR/files/peluciapet_files_$timestamp.tar.gz"
    
    log_message "INFO" "Iniciando backup dos arquivos..."
    
    # Lista de diretórios/arquivos para backup
    local files_to_backup=(
        "$WEB_DIR/admin"
        "$WEB_DIR/css"
        "$WEB_DIR/js"
        "$WEB_DIR/images"
        "$WEB_DIR/uploads"
        "$WEB_DIR/*.html"
        "$WEB_DIR/*.php"
        "$WEB_DIR/.htaccess"
    )
    
    # Criar arquivo tar comprimido
    tar -czf "$backup_file" \
        --exclude="*.log" \
        --exclude="*.tmp" \
        --exclude="cache/*" \
        --exclude="temp/*" \
        "${files_to_backup[@]}" 2>/dev/null
    
    if [[ $? -eq 0 ]]; then
        local file_size=$(du -h "$backup_file" | cut -f1)
        log_message "INFO" "Backup dos arquivos concluído: $backup_file ($file_size)"
        echo "$backup_file"
        return 0
    else
        log_message "ERROR" "Falha no backup dos arquivos"
        rm -f "$backup_file"
        return 1
    fi
}

# Backup dos logs
backup_logs() {
    local timestamp=$(date '+%Y%m%d_%H%M%S')
    local backup_file="$BACKUP_DIR/logs/peluciapet_logs_$timestamp.tar.gz"
    
    log_message "INFO" "Iniciando backup dos logs..."
    
    # Lista de arquivos de log
    local log_files=(
        "/var/log/apache2/access.log"
        "/var/log/apache2/error.log"
        "/var/log/mysql/error.log"
        "$WEB_DIR/logs/*.log"
        "$LOG_FILE"
    )
    
    # Criar arquivo tar comprimido (ignorar arquivos inexistentes)
    tar -czf "$backup_file" "${log_files[@]}" 2>/dev/null
    
    if [[ $? -eq 0 ]]; then
        local file_size=$(du -h "$backup_file" | cut -f1)
        log_message "INFO" "Backup dos logs concluído: $backup_file ($file_size)"
        echo "$backup_file"
        return 0
    else
        log_message "WARNING" "Backup dos logs falhou ou não há logs para backup"
        return 1
    fi
}

# =====================================================
# Funções de Manutenção
# =====================================================

# Limpeza de backups antigos
cleanup_old_backups() {
    log_message "INFO" "Iniciando limpeza de backups antigos..."
    
    local total_removed=0
    local dirs=("$BACKUP_DIR/database" "$BACKUP_DIR/files" "$BACKUP_DIR/logs")
    
    for dir in "${dirs[@]}"; do
        if [[ -d "$dir" ]]; then
            # Remover arquivos mais antigos que RETENTION_DAYS
            local old_files=$(find "$dir" -type f -mtime +$RETENTION_DAYS)
            
            if [[ -n "$old_files" ]]; then
                echo "$old_files" | while read -r file; do
                    rm -f "$file"
                    if [[ $? -eq 0 ]]; then
                        log_message "INFO" "Arquivo antigo removido: $file"
                        ((total_removed++))
                    fi
                done
            fi
            
            # Manter apenas os MAX_BACKUPS mais recentes
            local file_count=$(find "$dir" -type f | wc -l)
            if [[ $file_count -gt $MAX_BACKUPS ]]; then
                local excess=$((file_count - MAX_BACKUPS))
                find "$dir" -type f -printf '%T@ %p\n' | sort -n | head -n $excess | cut -d' ' -f2- | while read -r file; do
                    rm -f "$file"
                    if [[ $? -eq 0 ]]; then
                        log_message "INFO" "Backup excedente removido: $file"
                        ((total_removed++))
                    fi
                done
            fi
        fi
    done
    
    log_message "INFO" "Limpeza concluída. Arquivos removidos: $total_removed"
}

# Verificar integridade dos backups
verify_backup_integrity() {
    local db_backup=$1
    local files_backup=$2
    
    log_message "INFO" "Verificando integridade dos backups..."
    
    local integrity_ok=true
    
    # Verificar backup do banco
    if [[ -n "$db_backup" ]] && [[ -f "$db_backup" ]]; then
        gzip -t "$db_backup" 2>/dev/null
        if [[ $? -eq 0 ]]; then
            log_message "INFO" "Integridade do backup do banco: OK"
        else
            log_message "ERROR" "Integridade do backup do banco: FALHA"
            integrity_ok=false
        fi
    fi
    
    # Verificar backup dos arquivos
    if [[ -n "$files_backup" ]] && [[ -f "$files_backup" ]]; then
        tar -tzf "$files_backup" >/dev/null 2>&1
        if [[ $? -eq 0 ]]; then
            log_message "INFO" "Integridade do backup dos arquivos: OK"
        else
            log_message "ERROR" "Integridade do backup dos arquivos: FALHA"
            integrity_ok=false
        fi
    fi
    
    if [[ "$integrity_ok" == true ]]; then
        return 0
    else
        return 1
    fi
}

# Gerar relatório de backup
generate_backup_report() {
    local start_time=$1
    local end_time=$2
    local db_backup=$3
    local files_backup=$4
    local logs_backup=$5
    local status=$6
    
    local duration=$((end_time - start_time))
    local report_file="$BACKUP_DIR/backup_report_$(date '+%Y%m%d_%H%M%S').txt"
    
    cat > "$report_file" << EOF
=====================================================
RELATÓRIO DE BACKUP - SISTEMA PELÚCIAPET
=====================================================

Data/Hora: $(date '+%Y-%m-%d %H:%M:%S')
Status: $status
Duração: ${duration}s

ARQUIVOS GERADOS:
- Banco de Dados: ${db_backup:-"FALHA"}
- Arquivos Sistema: ${files_backup:-"FALHA"}
- Logs: ${logs_backup:-"FALHA"}

ESTATÍSTICAS:
- Tamanho Banco: $(if [[ -f "$db_backup" ]]; then du -h "$db_backup" | cut -f1; else echo "N/A"; fi)
- Tamanho Arquivos: $(if [[ -f "$files_backup" ]]; then du -h "$files_backup" | cut -f1; else echo "N/A"; fi)
- Tamanho Logs: $(if [[ -f "$logs_backup" ]]; then du -h "$logs_backup" | cut -f1; else echo "N/A"; fi)

ESPAÇO EM DISCO:
$(df -h "$BACKUP_DIR")

CONFIGURAÇÕES:
- Retenção: $RETENTION_DAYS dias
- Máximo Backups: $MAX_BACKUPS
- Diretório: $BACKUP_DIR

=====================================================
EOF

    log_message "INFO" "Relatório gerado: $report_file"
    echo "$report_file"
}

# =====================================================
# Função Principal
# =====================================================

main() {
    local start_time=$(date +%s)
    local status="SUCESSO"
    local db_backup=""
    local files_backup=""
    local logs_backup=""
    
    log_message "INFO" "=== INICIANDO BACKUP PELUCIAPET ==="
    
    # Verificar dependências
    if ! check_dependencies; then
        status="FALHA"
        send_notification "Backup PelúciaPet - FALHA" "Dependências faltando" "error"
        exit 1
    fi
    
    # Criar diretórios
    if ! create_directories; then
        status="FALHA"
        send_notification "Backup PelúciaPet - FALHA" "Erro ao criar diretórios" "error"
        exit 1
    fi
    
    # Testar conexão com banco
    if ! test_database_connection; then
        status="FALHA"
        send_notification "Backup PelúciaPet - FALHA" "Erro de conexão com banco" "error"
        exit 1
    fi
    
    # Executar backups
    db_backup=$(backup_database)
    if [[ $? -ne 0 ]]; then
        status="FALHA PARCIAL"
    fi
    
    files_backup=$(backup_files)
    if [[ $? -ne 0 ]]; then
        status="FALHA PARCIAL"
    fi
    
    logs_backup=$(backup_logs)
    # Logs backup é opcional, não afeta status principal
    
    # Verificar integridade
    if ! verify_backup_integrity "$db_backup" "$files_backup"; then
        status="FALHA"
    fi
    
    # Limpeza de backups antigos
    cleanup_old_backups
    
    # Finalizar
    local end_time=$(date +%s)
    local report_file=$(generate_backup_report "$start_time" "$end_time" "$db_backup" "$files_backup" "$logs_backup" "$status")
    
    log_message "INFO" "=== BACKUP CONCLUÍDO: $status ==="
    
    # Enviar notificação
    local message="Backup concluído com status: $status\n\nArquivos:\n- DB: $db_backup\n- Files: $files_backup\n- Logs: $logs_backup\n\nRelatório: $report_file"
    
    if [[ "$status" == "SUCESSO" ]]; then
        send_notification "Backup PelúciaPet - Sucesso" "$message" "success"
        exit 0
    else
        send_notification "Backup PelúciaPet - $status" "$message" "warning"
        exit 1
    fi
}

# =====================================================
# Execução
# =====================================================

# Verificar se está sendo executado como root (recomendado)
if [[ $EUID -eq 0 ]]; then
    log_message "WARNING" "Executando como root. Considere usar usuário específico."
fi

# Verificar argumentos
case "${1:-}" in
    "test")
        log_message "INFO" "Modo de teste ativado"
        test_database_connection
        exit $?
        ;;
    "cleanup")
        log_message "INFO" "Executando apenas limpeza"
        cleanup_old_backups
        exit 0
        ;;
    "help"|"-h"|"--help")
        echo "Uso: $0 [test|cleanup|help]"
        echo "  test    - Testar conexão com banco"
        echo "  cleanup - Executar apenas limpeza"
        echo "  help    - Mostrar esta ajuda"
        exit 0
        ;;
    "")
        # Execução normal
        main
        ;;
    *)
        echo "Argumento inválido: $1"
        echo "Use '$0 help' para ver opções disponíveis"
        exit 1
        ;;
esac

