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

        console.log(page);
        
        document.getElementById('price-loading').style.display = 'block';

        fetch(`${ajax_price_pagination_url}&page=${page}&itemsPerPage=${itemsPerPage}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('price-loading').style.display = 'none';

                if (data.status === 'success') {
                    var priceContainer = document.getElementById('price-data');
                    priceContainer.style.display = 'block';

                    var priceHTML = '<table class="table">';
                    priceHTML += '<thead><tr>';
                    priceHTML += '<th>Producto</th>';
                    priceHTML += '<th>Código Lista</th>';
                    priceHTML += '<th>Precio</th>';
                    priceHTML += '<th>Prestashop Precio</th>';
                    priceHTML += '</tr></thead>';
                    priceHTML += '<tbody>';

                    data.data.forEach(priceItem => {
                        if (priceItem.encontrado === 1) {
                            priceHTML += `<tr><td>${priceItem.reference}</td>`;
                            priceHTML += `<td>${priceItem.codLista}</td>`;
                            priceHTML += `<td>${priceItem.price}</td>`;
                            priceHTML += `<td>${priceItem.prestashop_price}</td></tr>`;
                        }
                    });

                    priceHTML += '</tbody></table>';
                    priceContainer.innerHTML = priceHTML;

                    // Actualizamos currentPage y totalPages para usarlos globalmente
                    currentPage = data.page;
                    totalPages = data.totalPages;
                    document.getElementById('page-info').innerText = `Página ${currentPage} de ${totalPages}`;

                    document.getElementById('prev-page').disabled = (currentPage <= 1);
                    document.getElementById('next-page').disabled = (currentPage >= totalPages);
                }
            })
            .catch(error => {
                document.getElementById('price-loading').innerHTML = '<div class="alert alert-danger">Error al cargar los datos de price.</div>';
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

    // Manejar clic en el botón de actualización
    document.getElementById("update-found-products").addEventListener("click", function () {
        // Mostrar mensaje de carga
        document.getElementById('price-loading').style.display = 'block';

        // Realizar solicitud AJAX para actualizar productos
        fetch(`${ajax_price_update_url}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('price-loading').style.display = 'none';

            if (data.status === 'success') {
                alert("Productos actualizados correctamente: " + data.updated + "no actualizados: " + data.errors);
            } else {
                alert("Error en la actualización de productos: " + data.message);
            }
        })
        .catch(error => {
            document.getElementById('price-loading').style.display = 'none';
            alert("Error en la actualización de productos.");
            console.error(error);
        });
    });
});

