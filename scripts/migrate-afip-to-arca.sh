#!/bin/bash
# Migration script from AFIP SDK paths to Laravel ARCA paths

set -e

SOURCE_DIR="storage/app/afip/empresas"
TARGET_DIR="storage/app/public"

echo "🔄 Iniciando migración de certificados..."
echo "Origen: $SOURCE_DIR"
echo "Destino: $TARGET_DIR"
echo ""

if [ ! -d "$SOURCE_DIR" ]; then
    echo "❌ No existe $SOURCE_DIR"
    exit 1
fi

mkdir -p "$TARGET_DIR"
MIGRATED=0

for CUIT_DIR in "$SOURCE_DIR"/*; do
    if [ -d "$CUIT_DIR" ]; then
        CUIT=$(basename "$CUIT_DIR")
        TARGET_CUIT_DIR="$TARGET_DIR/$CUIT"
        
        # Crear directorio destino
        mkdir -p "$TARGET_CUIT_DIR"
        
        # Migrar certificado: certificate.crt → cert.crt
        if [ -f "$CUIT_DIR/certificate.crt" ]; then
            cp "$CUIT_DIR/certificate.crt" "$TARGET_CUIT_DIR/cert.crt"
            echo "✅ Migrado certificado CUIT $CUIT: cert.crt"
            MIGRATED=$((MIGRATED + 1))
        else
            echo "⚠️  No encontrado certificate.crt para CUIT $CUIT"
        fi
        
        # Migrar clave privada: private.key → key.key
        if [ -f "$CUIT_DIR/private.key" ]; then
            cp "$CUIT_DIR/private.key" "$TARGET_CUIT_DIR/key.key"
            echo "✅ Migrada clave privada CUIT $CUIT: key.key"
        else
            echo "⚠️  No encontrado private.key para CUIT $CUIT"
        fi
        
        echo ""
    fi
done

if [ $MIGRATED -eq 0 ]; then
    echo "⚠️  No se migraron certificados (directorio vacío o estructura diferente)"
    exit 0
fi

echo "✅ Migración completada: $MIGRATED certificados"
echo ""
echo "📂 Certificados disponibles en $TARGET_DIR:"
ls -la "$TARGET_DIR"/*/
