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
    console.log("listo");
    var currentPage = 1;
    var itemsPerPage = 100;

    function loadPage(page) {
        document.getElementById('stock-loading').style.display = 'block';

        // Modificar fetch para agregar correctamente los parámetros
        fetch(`${ajax_stock_pagination_url}&page=${page}&itemsPerPage=${itemsPerPage}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('stock-loading').style.display = 'none';
                
                if (data.status === 'success') {
                    var stockContainer = document.getElementById('stock-data');
                    stockContainer.style.display = 'block';

                    var stockHTML = '<table class="table">';
                    stockHTML += '<thead><tr><th>Producto</th><th>Stock</th><th>Prestashop Stock</th><th>Encontrado</th></tr></thead><tbody>';
                    
                    data.data.forEach(stockItem => {
                        stockHTML += `<tr><td>${stockItem.reference}</td>`;
                        stockHTML += `<td>${stockItem.stock}</td>`;
                        stockHTML += `<td>${stockItem.prestashop_stock}</td>`;
                        stockHTML += `<td>${stockItem.estado ? "<i class='icon-circle-blank'></i>" : "<i class='icon-remove'></i>"}</td></tr>`;
                    });

                    stockHTML += '</tbody></table>';
                    stockContainer.innerHTML = stockHTML;

                    document.getElementById('page-info').innerText = 'Página ' + data.page + ' de ' + data.totalPages;
                    currentPage = data.page;

                    document.getElementById('prev-page').disabled = (currentPage <= 1);
                    document.getElementById('next-page').disabled = (currentPage >= data.totalPages);
                }
            })
            .catch(error => {
                document.getElementById('stock-loading').innerHTML = '<div class="alert alert-danger">Error al cargar los datos de stock.</div>';
            });
    }

    document.getElementById('next-page').addEventListener('click', function () {
        loadPage(currentPage + 1);
    });

    document.getElementById('prev-page').addEventListener('click', function () {
        if (currentPage > 1) {
            loadPage(currentPage - 1);
        }
    });

    loadPage(1);
});