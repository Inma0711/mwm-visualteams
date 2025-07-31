# MWM Visual Teams - Product Options Extension

Extensión para YITH WooCommerce Product Add-ons que permite calcular opciones sobre el precio total del producto.

## 🚀 Funcionalidades

### ✅ Funcionalidad Principal
- Integración con YITH WooCommerce Product Add-ons
- Cálculo de precios dinámicos en tiempo real
- Soporte para múltiples opciones de producto

### 🔄 Funcionalidad: Cálculo 70% Automático
- **Activación**: Solo se aplica cuando el botón "Calcular sobre precio total del producto" está activado en el panel de WordPress
- **Opciones Aplicables**: Cromato y Olografico en la sección SUPPORTI SPECIALI
- **Fórmula Aplicada**: 
  1. Se resta el precio de la opción del total del producto
  2. Se calcula el 70% del precio base resultante
  3. Se asigna ese nuevo precio a la opción seleccionada
  4. Se calcula el nuevo total del producto
- **Visualización**: Los resultados se muestran automáticamente en "Totale opzioni" y "Totale ordine"

## 📋 Ejemplos de Cálculo

### Ejemplo 1 (Pedro)
- **Precio total original**: 579,00 €
- **Precio de la opción**: 154,00 €
- **Cálculo**:
  1. 579,00 € - 154,00 € = 425,00 € (precio base)
  2. 70% de 425,00 € = 297,50 €
  3. Nuevo precio de la opción: 297,50 €
  4. Nuevo total: 425,00 € + 297,50 € = **722,50 €**

### Ejemplo 2
- **Precio total original**: 340,00 €
- **Precio de la opción**: 238,00 €
- **Cálculo**:
  1. 340,00 € - 238,00 € = 102,00 € (precio base)
  2. 70% de 102,00 € = 71,40 €
  3. Nuevo precio de la opción: 71,40 €
  4. Nuevo total: 102,00 € + 71,40 € = **173,40 €**

## 🛠️ Instalación

1. Sube el plugin a la carpeta `/wp-content/plugins/mwm-visualteams/`
2. Activa el plugin desde el panel de administración de WordPress
3. Ve a YITH WooCommerce Product Add-ons
4. En la sección SUPPORTI SPECIALI, activa el botón "Calcular sobre precio total del producto" para las opciones Cromato y Olografico

## 🎯 Configuración

### Para Activar el Cálculo 70%:
1. Ve al panel de administración de WordPress
2. Navega a YITH WooCommerce Product Add-ons
3. Edita la sección SUPPORTI SPECIALI
4. Para las opciones Cromato y Olografico, activa el botón "Calcular sobre precio total del producto"
5. Guarda los cambios

### Comportamiento:
- **Botón Activado**: Se aplica el cálculo del 70% automáticamente
- **Botón Desactivado**: Se usa el cálculo normal de YITH
- **Resultado**: Se muestra en "Totale opzioni" y se suma al precio base en "Totale ordine"

## 🧪 Pruebas

### Para Probar el Cálculo:
1. Ve a cualquier página de producto con opciones SUPPORTI SPECIALI
2. Asegúrate de que el botón "Calcular sobre precio total del producto" esté activado en el panel de WordPress
3. Selecciona Cromato o Olografico
4. Verifica que el precio en "Totale opzioni" muestre el resultado del cálculo del 70%
5. Verifica que "Totale ordine" muestre la suma correcta

## 📁 Estructura del Plugin

```
mwm-visualteams/
├── mwm-visualteams.php          # Archivo principal del plugin
├── includes/
│   ├── class-mwm-visualteams-frontend.php  # Funcionalidad frontend
│   ├── class-mwm-visualteams-admin.php     # Funcionalidad admin
│   └── class-mwm-visualteams-cart.php      # Funcionalidad carrito
├── assets/
│   └── js/
│       └── frontend.js          # JavaScript frontend
└── README.md                    # Este archivo
```

## 🔧 Desarrollo

### Archivos Principales:
- **`class-mwm-visualteams-frontend.php`**: Maneja la detección de opciones y el cálculo del 70%
- **`class-mwm-visualteams-admin.php`**: Agrega el botón "Calcular sobre precio total del producto" al panel de YITH
- **`frontend.js`**: JavaScript que maneja los cambios de opciones y actualiza los precios en tiempo real

### Hooks Utilizados:
- `woocommerce_after_single_product_summary`: Para inicializar el cálculo
- `wp_ajax_mwm_check_total_calculation`: Para verificar si el cálculo total está habilitado
- `yith_wapo_save_addon`: Para guardar la configuración del botón

## 📝 Notas

- El cálculo del 70% solo se aplica a las opciones Cromato y Olografico en SUPPORTI SPECIALI
- El botón debe estar activado en el panel de WordPress para que funcione
- Los precios se actualizan automáticamente sin recargar la página
- Compatible con WooCommerce y YITH WooCommerce Product Add-ons 