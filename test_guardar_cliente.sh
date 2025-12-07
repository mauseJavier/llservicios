#!/bin/bash

# Script de prueba para el endpoint de guardar cliente
# Uso: ./test_guardar_cliente.sh

echo "=========================================="
echo "Test: Guardar Cliente y Vincular a Empresa"
echo "=========================================="

# URL del endpoint (ajustar según tu configuración)
BASE_URL="http://localhost:8000/api"
ENDPOINT="$BASE_URL/cliente/guardar"

echo ""
echo "URL del endpoint: $ENDPOINT"
echo ""

# Datos de prueba
echo "=========================================="
echo "Test 1: Guardar cliente con todos los campos"
echo "=========================================="

curl -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "Juan Pérez Test",
    "correo": "juan.perez.test@example.com",
    "telefono": "123456789",
    "dni": 12345678,
    "domicilio": "Calle Falsa 123",
    "empresa_id": 1
  }' | jq '.'

echo ""
echo ""

# Test 2: Guardar cliente sin campos opcionales
echo "=========================================="
echo "Test 2: Guardar cliente solo con campos requeridos"
echo "=========================================="

curl -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "María López Test",
    "empresa_id": 1
  }' | jq '.'

echo ""
echo ""

# Test 3: Error - sin nombre
echo "=========================================="
echo "Test 3: Error - Sin nombre (campo requerido)"
echo "=========================================="

curl -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "correo": "test@example.com",
    "empresa_id": 1
  }' | jq '.'

echo ""
echo ""

# Test 4: Error - sin empresa_id
echo "=========================================="
echo "Test 4: Error - Sin empresa_id (campo requerido)"
echo "=========================================="

curl -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "Pedro García Test"
  }' | jq '.'

echo ""
echo ""

# Test 5: Error - empresa_id inexistente
echo "=========================================="
echo "Test 5: Error - empresa_id inexistente"
echo "=========================================="

curl -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "Ana Martínez Test",
    "empresa_id": 99999
  }' | jq '.'

echo ""
echo ""

# Test 6: Error - correo inválido
echo "=========================================="
echo "Test 6: Error - Correo inválido"
echo "=========================================="

curl -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "Carlos Sánchez Test",
    "correo": "correo-invalido",
    "empresa_id": 1
  }' | jq '.'

echo ""
echo ""

# Test 7: Cliente existente - mismo nombre
echo "=========================================="
echo "Test 7: Cliente existente - Mismo nombre (debe devolver cliente existente)"
echo "=========================================="

curl -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "Juan Pérez Test",
    "correo": "otro.correo@example.com",
    "telefono": "987654321",
    "empresa_id": 1
  }' | jq '.'

echo ""
echo ""

# Test 8: Cliente existente vinculado a otra empresa
echo "=========================================="
echo "Test 8: Cliente existente - Vincular a otra empresa"
echo "=========================================="
echo "Nota: Si la empresa con ID 2 no existe, este test fallará"

curl -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "Juan Pérez Test",
    "empresa_id": 2
  }' | jq '.'

echo ""
echo ""
echo "=========================================="
echo "Tests completados"
echo "=========================================="
