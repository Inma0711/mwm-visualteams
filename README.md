# Plugin de Opciones de Producto - WooCommerce

## Descripción
Plugin personalizado para WooCommerce que permite configurar opciones de producto con cálculos de precio avanzados.

## Tareas Actuales

### ✅ Tarea Principal: Cálculo sobre Precio Total

**Objetivo:** Implementar una nueva opción que permita calcular el precio de una opción sobre el precio total del producto en lugar del precio base.

#### Funcionalidades a desarrollar:

1. **Nueva Opción de Configuración**
   - Agregar checkbox: "Calcular sobre precio total del producto"
   - Estado: activado/desactivado

2. **Lógica de Cálculo**
   - **Cuando está activado:** El cálculo se realiza sobre el precio total del producto (excluyendo la opción actual)
   - **Cuando está desactivado:** El cálculo se realiza sobre el precio base del producto (comportamiento actual)

3. **Integración con Carrito**
   - Los cálculos deben realizarse directamente en el carrito
   - No se debe calcular antes de agregar al carrito
   - Asegurar que los cambios se reflejen inmediatamente en el carrito

#### Especificaciones Técnicas:
- El cálculo debe ser dinámico y actualizarse en tiempo real
- Mantener compatibilidad con el sistema de opciones existente
- Asegurar que no afecte el rendimiento del carrito

## Estructura del Proyecto
```
mwm-visualteams/
├── README.md
├── [archivos del plugin]
└── [documentación adicional]
```

## Instalación
[Instrucciones de instalación pendientes]

## Uso
[Instrucciones de uso pendientes]

## Contribución
[Guías de contribución pendientes]

---
*Desarrollado para WooCommerce* 