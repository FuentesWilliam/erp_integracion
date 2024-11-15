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
    let currentPage = 1;
    let totalPages = 1;  // Inicializamos totalPages para evitar errores
    const itemsPerPage = 100;

    function loadPage(page) {        
        document.getElementById('stock-loading').style.display = 'block';
        let class_spanStock = 'badge badge-success rounded';
        let class_spanPrice = 'badge badge-success rounded';

        fetch(`${pagination_url}&page=${page}&itemsPerPage=${itemsPerPage}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('stock-loading').style.display = 'none';

                if (data.status === 'success') {

                    if(data.udpStock != 0){
                        document.getElementById('update-stock-products').removeAttribute('disabled');
                        class_spanStock = 'badge badge-warning rounded';
                    }   

                    if(data.udpPrice != 0 ){
                        document.getElementById('update-price-products').removeAttribute('disabled');
                        class_spanPrice= 'badge badge-warning rounded';
                    }

                    var stockContainer = document.getElementById('stock-data');
                    stockContainer.style.display = 'block';

                    var HTML = '<table class="table">';
                    HTML += '<thead><tr>';
                    HTML += '<th>Producto</th>';
                    HTML += '<th>Stock</th>';
                    HTML += `<th>Prestashop Stock <span class="${class_spanStock}" title="cantidad de Productos para actualizar">${data.udpStock}</span"></th>`;
                    HTML += '<th>Precio</th>';
                    HTML += `<th>Prestashop Precio <span class="${class_spanPrice}" title="cantidad de Productos para actualizar">${data.udpPrice}</span></th>`;
                    HTML += '</tr></thead>';
                    HTML += '<tbody>';

                    data.data.forEach(stockItem => {
                        HTML += `<tr><td>${stockItem.reference}</td>`;
                        HTML += `<td>${stockItem.stock}</td>`;
                        HTML += `<td>${stockItem.prestashop_stock}</td>`;
                        HTML += `<td>${stockItem.price}</td>`;
                        HTML += `<td>${stockItem.prestashop_price}</td></tr>`;
                    });

                    HTML += '</tbody></table>';
                    stockContainer.innerHTML = HTML;

                    // Actualizamos currentPage y totalPages para usarlos globalmente
                    currentPage = data.page;
                    totalPages = data.totalPages;
                    document.getElementById('page-info').innerText = `Página ${currentPage} de ${totalPages}`;

                    document.getElementById('prev-page').disabled = (currentPage <= 1);
                    document.getElementById('next-page').disabled = (currentPage >= totalPages);
                }
            })
            .catch(error => {
                document.getElementById('stock-loading').innerHTML = '<div class="alert alert-danger">Error al cargar los datos de stock.</div>';
                console.error('Error en el fetch:', error);
            });
    }

    // Control de botones de navegación
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

    loadPage(1); // Cargar la primera página
});