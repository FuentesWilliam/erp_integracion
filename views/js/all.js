/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
* 
* 
*/

document.addEventListener("DOMContentLoaded", function () {
    // VARIABLES GLOBALES
    let currentPage = 1;
    let totalPages = 1; // Inicializamos totalPages
    const itemsPerPage = 100;

    // FUNCIÓN PARA CARGAR UNA PÁGINA
    function loadPage(page) {        
        const stockLoading = document.getElementById('data-loading');
        stockLoading.style.display = 'block';

        let class_spanStock = 'badge badge-success rounded';
        let class_spanPrice = 'badge badge-success rounded';

        fetch(`${pagination_url}&page=${page}&itemsPerPage=${itemsPerPage}`)
            .then(response => response.json())
            .then(data => {
                stockLoading.style.display = 'none';

                if (data.status === 'success') {
                    // Actualizar botones según el estado de los datos
                    if (data.udpStock != 0) {
                        document.getElementById('update-stock-products').removeAttribute('disabled');
                        class_spanStock = 'badge badge-warning rounded';
                    }   
                    if (data.udpPrice != 0) {
                        document.getElementById('update-price-products').removeAttribute('disabled');
                        class_spanPrice = 'badge badge-warning rounded';
                    }

                    // Renderizar tabla con datos de stock
                    renderStockTable(data, class_spanStock, class_spanPrice);

                    // Actualizar navegación de páginas
                    currentPage = data.page;
                    totalPages = data.totalPages;
                    document.getElementById('page-info').innerText = `Página ${currentPage} de ${totalPages}`;
                    document.getElementById('prev-page').disabled = (currentPage <= 1);
                    document.getElementById('next-page').disabled = (currentPage >= totalPages);
                }
            })
            .catch(error => {
                stockLoading.innerHTML = '<div class="alert alert-danger">Error al cargar los datos de stock.</div>';
                console.error('Error en el fetch:', error);
            });
    }

    // FUNCIÓN PARA RENDERIZAR LA TABLA DE STOCK
    function renderStockTable(data, class_spanStock, class_spanPrice) {
        const stockContainer = document.getElementById('stock-data');
        stockContainer.style.display = 'block';

        let HTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Stock</th>
                        <th>Prestashop Stock <span class="${class_spanStock}" title="Cantidad de productos para actualizar">${data.udpStock}</span></th>
                        <th>Precio</th>
                        <th>Prestashop Precio <span class="${class_spanPrice}" title="Cantidad de productos para actualizar">${data.udpPrice}</span></th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.data.forEach(stockItem => {
            // Verificamos si hay diferencias en el stock y/o en el precio
            const hasStockDifference = stockItem.stock !== stockItem.prestashop_stock;
            const hasPriceDifference = stockItem.price !== stockItem.prestashop_price;

            let stockCellClass = '';  // Clase para la celda de stock
            let priceCellClass = '';  // Clase para la celda de precio
            let iconHTML = '';        // Variable para el ícono

            // Si hay diferencia en el stock, aplicamos la clase correspondiente
            if (hasStockDifference) {
                stockCellClass = 'highlight-stock-difference';
                iconHTML = '<i class="fas fa-exclamation-circle highlight-difference-icon"></i>'; // Ícono para stock
            }

            // Si hay diferencia en el precio, aplicamos la clase correspondiente
            if (hasPriceDifference) {
                priceCellClass = 'highlight-price-difference';
                iconHTML = '<i class="material-icons">warning</i>'; // Ícono para precio
            }

            // Construimos el HTML de la tabla con las celdas destacadas y el ícono
            HTML += `
                <tr>
                    <td>${iconHTML} ${stockItem.reference}</td>
                    <td class="${stockCellClass}">${stockItem.stock}</td>
                    <td class="${stockCellClass}">${stockItem.prestashop_stock}</td>
                    <td class="${priceCellClass}">${stockItem.price}</td>
                    <td class="${priceCellClass}">${stockItem.prestashop_price}</td>
                </tr>
            `;
        });

        HTML += '</tbody></table>';
        stockContainer.innerHTML = HTML;
    }

    // MANEJO DE BOTONES DE NAVEGACIÓN
    document.getElementById('next-page').addEventListener('click', function () {
        if (currentPage < totalPages) {
            loadPage(Number(currentPage) + 1);
        }
    });

    document.getElementById('prev-page').addEventListener('click', function () {
        if (currentPage > 1) {
            loadPage(currentPage - 1);
        }
    });

    // Función para mostrar la alerta
    function showAlert(message, type) {
        const alertDiv = document.getElementById('erp-alert');
        alertDiv.className = `alert alert-${type}`; // Actualiza la clase según el tipo
        alertDiv.textContent = message; // Muestra el mensaje
        alertDiv.style.display = 'block'; // Muestra el div
    }

    // Función para ocultar la alerta
    function hideAlert() {
        const alertDiv = document.getElementById('erp-alert');
        alertDiv.style.display = 'none'; // Oculta el div
    }

    // Función genérica para manejar la acción del botón con mensajes de alerta
    const handleButtonClickWithSyncService = (buttonId, action, startMessage, successMessage, errorMessage) => {
        const button = document.getElementById(buttonId);

        button.addEventListener('click', async () => {
            // Muestra el mensaje inicial de sincronización
            showAlert(startMessage, 'info');
            button.classList.add('loading'); // Activa el spinner

            try {
                // Llamamos al servidor PHP para ejecutar la acción
                const response = await fetch(`${action}`);
                const data = await response.json(); // Parseamos la respuesta JSON

                if (data.status) {
                    showAlert(successMessage + `Actualizados: ${data.updated}`, 'success'); // Muestra mensaje de éxito
                } else {
                    showAlert(errorMessage, 'danger'); // Muestra el mensaje de error
                }
            } catch (error) {
                showAlert(errorMessage, 'danger'); // Muestra mensaje de error
                console.error('Error en la solicitud:', error);
            } finally {
                button.classList.remove('loading'); // Quita el spinner
                setTimeout(hideAlert, 5000); // Oculta la alerta después de 5 segundos
                loadPage(currentPage);
            }
        });
    };

    // Asocia los botones con las acciones correspondientes
    handleButtonClickWithSyncService(
        'update-sync', // Botón para sincronizar
        `${erp_url}`, // Acción en PHP (función syncStockAndPrices)
        'Sincronizando con el ERP, por favor espere...',
        'La sincronización con el ERP se completó exitosamente.',
        'Error al sincronizar con el ERP. Por favor, inténtelo nuevamente.'
    );

    handleButtonClickWithSyncService(
        'update-stock-products', // Botón para actualizar stock
        `${stock_url}`,
        'Actualizando stock, por favor espere...',
        'El stock se actualizó exitosamente.',
        'Error al actualizar el stock. Por favor, inténtelo nuevamente.'
    );

    handleButtonClickWithSyncService(
        'update-price-products', // Botón para actualizar precios
        `${price_url}`,
        'Actualizando precios, por favor espere...',
        'Los precios se actualizaron exitosamente.',
        'Error al actualizar los precios. Por favor, inténtelo nuevamente.'
    );

    // CARGAR LA PRIMERA PÁGINA AL INICIO
    loadPage(1);
});