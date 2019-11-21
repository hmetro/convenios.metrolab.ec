 // the html to: <a href="javascript:void(0);" data-navigo>About</a>
 // valores para el docuemnto CTAs
 // Rutas de la pagina
 $(function() {
     fetch('api/', {
         method: "GET",
         headers: {
             'Cache-Control': 'no-cache',
             "X-REQUEST-T": mx,
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('.footer').after(data);
         route();
     }).catch(function(err) {
         console.error(err);
     });
 });

 function route() {
     router.on({
         '/:id_user/editar': function(params) {
             getEditUsuario(params.id_user);
         },
         '/:id_user/cambiar-contraseña': function(params) {
             getEditPass(params.id_user);
         },
         '/:id_user/timeline': function(params) {
             getConvenio(params.id_user);
         },
         '/:id_convenio/convenio': function(params) {
             getConvenio(params.id_convenio);
         },
         '/:id_convenio/editar-convenio': function(params) {
             getEditConvenio(params.id_convenio);
         },
         '/:id_user/datos-personales': function(params) {
             getUsuario(params.id_user);
         },
         '*/:id_user': function(params) {
             getPerfil(params.id_user);
             // $('[data-toggle="tooltip"]').tooltip();
         }
     }).resolve();
 }

 function loadLinks(data) {
     $('#dt-n-a').html(data.customData.user_data.nombres + ' ' + data.customData.user_data.apellidos);
     $('#dt-email').html(data.customData.email);
     $('#dt-user').html(data.customData.user);
     $('#dt-rol').html(data.customData.name_rol);
     $('#dt-cv').attr('href', '/perfil#!/' + data.customData.convenio + '/convenio');
     $('#dt-dp').attr('href', '/perfil#!/' + data.customData.id + '/datos-personales');
     $('#dt-rp').attr('href', '/perfil#!/' + data.customData.id + '/cambiar-contraseña');
 }
 // funcion obtiene datos de usuario
 function getEditPass(id) {
     fetch('api/usuario', {
         method: "GET",
         headers: {
             "X-REQUEST-ID-USER": id,
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-editar-contraseña').html(), {
             user: data.customData.user,
             email: data.customData.email,
             nombres: data.customData.user_data.nombres,
             apellidos: data.customData.user_data.apellidos,
             id: data.customData.id,
         }));
         loadLinks(data);
         $('.editar').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             router.navigate('/' + $(this).attr('id') + '/editar');
         });
         $('.cambiar-contraseña').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             router.navigate('/' + $(this).attr('id') + '/cambiar-contraseña');
         });
     }).catch(function(err) {
         console.error(err);
     });
 }
 // funcion obtiene datos de usuario
 function getEditConvenio(id) {
     fetch('api/convenio', {
         method: "GET",
         headers: {
             "X-REQUEST-ID-CV": id,
             "X-REQUEST-TRACK": "view",
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-editar-convenio').html(), {
             'isologo': data.convenio.isologo,
             'dir': data.convenio.dir,
             'tel': data.convenio.tel,
             'email': data.convenio.email,
             'perfil_convenio': id,
         }));
         loadLinks(data);
         $('#btn-editar').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             _ini_edit_convenio(id);
         });
     }).catch(function(err) {
         console.error(err);
     });
 }
 // funcion obtiene datos de usuario
 function getEditUsuario(id) {
     fetch('api/usuario', {
         method: "GET",
         headers: {
             "X-REQUEST-ID-USER": id,
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-editar-datos').html(), {
             user: data.customData.user,
             email: data.customData.email,
             nombres: data.customData.user_data.nombres,
             apellidos: data.customData.user_data.apellidos,
             id: data.customData.id,
         }));
         loadLinks(data);
         $('.editar').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             router.navigate('/' + $(this).attr('id') + '/editar');
         });
         $('.cambiar-contraseña').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             router.navigate('/' + $(this).attr('id') + '/cambiar-contraseña');
         });
         $('#btn-editar').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             _ini_edit_user(data.customData.id);
         });
     }).catch(function(err) {
         console.error(err);
     });
 }

 function loadFile(event) {
     var reader = new FileReader();
     var fileName = document.getElementById('isologo').files[0].name;
     reader.onload = function() {
         var output = document.getElementById('temp_isologo');
         output.src = reader.result;
         $('.custom-file-label').html(fileName);
     };
     reader.readAsDataURL(event.target.files[0]);
 }
 // funcion obtiene datos de usuario
 function getEditPass(id) {
     fetch('api/usuario', {
         method: "GET",
         headers: {
             "X-REQUEST-ID-USER": id,
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-editar-contraseña').html(), {}));
         loadLinks(data);
         $('#btn-editar').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             _ini_edit_pass(data.customData.id);
         });
     }).catch(function(err) {
         console.error(err);
     });
 }
 // funcion obtiene datos de usuario
 function getUsuario(id) {
     fetch('api/usuario', {
         method: "GET",
         headers: {
             "X-REQUEST-ID-USER": id,
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-datos-personales').html(), {
             user: data.customData.user,
             email: data.customData.email,
             nombres: data.customData.user_data.nombres,
             apellidos: data.customData.user_data.apellidos,
             id: data.customData.id,
         }));
         loadLinks(data);
         $('.editar').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             router.navigate('/' + $(this).attr('id') + '/editar');
         });
         $('.cambiar-contraseña').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             router.navigate('/' + $(this).attr('id') + '/cambiar-contraseña');
         });
     }).catch(function(err) {
         console.error(err);
     });
 }
 // funcion obtiene datos de usuario
 function getPerfil(id) {
     fetch('api/usuario', {
         method: "GET",
         headers: {
             "X-REQUEST-ID-USER": id,
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-perfil').html(), {
             'perfil_id': perfil_id,
             'perfil_convenio': data.customData.convenio
         }));
         loadLinks(data);
     }).catch(function(err) {
         console.error(err);
     });
 }
 // funcion obtiene datos de usuario
 function getConvenio(id) {
     var myHeaders = new Headers();
     myHeaders.append('pragma', 'no-cache');
     myHeaders.append('cache-control', 'no-cache');
     myHeaders.append('X-REQUEST-ID-CV', id);
     myHeaders.append('X-REQUEST-TRACK', 'view');
     fetch('api/convenio', {
         method: "GET",
         headers: myHeaders,
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-convenio').html(), {
             'isologo': data.convenio.isologo,
             'dir': data.convenio.dir,
             'tel': data.convenio.tel,
             'email': data.convenio.email,
             'perfil_convenio': id,
         }));
         loadLinks(data);
         $('.editar').click(function(e) {
             e.stopImmediatePropagation();
             e.preventDefault();
             router.navigate('/' + $(this).attr('id') + '/editar-convenio');
         });
     }).catch(function(err) {
         console.error(err);
     });
 }

 function _ini_edit_pass(id) {
     $('#response').removeClass('alert-danger');
     $('#response').removeClass('alert-warning');
     $('#response').addClass('alert-warning');
     $("#response").html('Procesando...');
     $('#response').removeClass('d-none');
     $('#btn-editar').attr('disabled', true);
     fetch('api/perfil', {
         method: "POST",
         body: $('#pass_mx').serialize(),
         headers: {
             "Content-Type": "application/x-www-form-urlencoded",
             "X-REQUEST-ID-USER": id,
             "X-REQUEST-TRACK": "change-pass"
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         if (data.success) {
             $('#response').html(data.message).css('font-weight', 'bold');
             $("#response").removeClass('alert-warning');
             $("#response").addClass('alert-success');
             $('#btn-editar').removeAttr('disabled');
             $('#pass_mx').trigger("reset");
         } else {
             $('#response').html(data.message).css('font-weight', 'bold');
             $("#response").removeClass('alert-warning');
             $("#response").addClass('alert-danger');
             $('#btn-editar').removeAttr('disabled');
         }
     }).catch(function(err) {
         console.error(err);
     });
 };

 function _ini_edit_user(id) {
     $('#response').removeClass('alert-danger');
     $('#response').removeClass('alert-warning');
     $('#response').addClass('alert-warning');
     $("#response").html('Procesando...');
     $('#response').removeClass('d-none');
     $('#btn-editar').attr('disabled', true);
     fetch('api/perfil', {
         method: "POST",
         body: $('#datauser_mx').serialize(),
         headers: {
             "Content-Type": "application/x-www-form-urlencoded",
             "X-REQUEST-ID-USER": id,
             "X-REQUEST-TRACK": "edit"
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         if (data.success) {
             $('#response').html(data.message).css('font-weight', 'bold');
             $("#response").removeClass('alert-warning');
             $("#response").addClass('alert-success');
             $('#btn-editar').removeAttr('disabled');
             router.navigate('/' + id + '/datos-personales');
         } else {
             $('#response').html(data.message).css('font-weight', 'bold');
             $("#response").removeClass('alert-warning');
             $("#response").addClass('alert-danger');
             $('#btn-editar').removeAttr('disabled');
         }
     }).catch(function(err) {
         console.error(err);
     });
 };

 function _ini_edit_convenio(id) {
     $('#response').removeClass('alert-danger');
     $('#response').removeClass('alert-warning');
     $('#response').addClass('alert-warning');
     $("#response").html('Procesando...');
     $('#response').removeClass('d-none');
     $('#btn-editar').attr('disabled', true);
     var postData = new FormData($('#editarconvenio_mx')[0]);
     fetch('api/convenio', {
         method: "POST",
         body: postData,
         headers: {
             "X-REQUEST-ID-CV": id,
             "X-REQUEST-TRACK": "edit"
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         if (data.success) {
             $('#response').html(data.message).css('font-weight', 'bold');
             $("#response").removeClass('alert-warning');
             $("#response").addClass('alert-success');
             $('#btn-editar').removeAttr('disabled');
             router.navigate('/' + id + '/convenio');
         } else {
             $('#response').html(data.message).css('font-weight', 'bold');
             $("#response").removeClass('alert-warning');
             $("#response").addClass('alert-danger');
             $('#btn-editar').removeAttr('disabled');
         }
     }).catch(function(err) {
         console.error(err);
     });
 };

 function loadPdf(url) {
     // If absolute URL from the remote server is provided, configure the CORS
     // Loaded via <script> tag, create shortcut to access PDF.js exports.
     var pdfjsLib = window['pdfjs-dist/build/pdf'];
     // The workerSrc property shall be specified.
     pdfjsLib.GlobalWorkerOptions.workerSrc = '//mozilla.github.io/pdf.js/build/pdf.worker.js';
     var pdfDoc = null,
         pageNum = 1,
         pageRendering = false,
         pageNumPending = null,
         scale = 1.75,
         canvas = document.getElementById('the-canvas'),
         ctx = canvas.getContext('2d');
     /**
      * Get page info from document, resize canvas accordingly, and render page.
      * @param num Page number.
      */
     function renderPage(num) {
         pageRendering = true;
         // Using promise to fetch the page
         pdfDoc.getPage(num).then(function(page) {
             var viewport = page.getViewport({
                 scale: scale
             });
             canvas.height = viewport.height;
             canvas.width = viewport.width;
             // Render PDF page into canvas context
             var renderContext = {
                 canvasContext: ctx,
                 viewport: viewport
             };
             var renderTask = page.render(renderContext);
             // Wait for rendering to finish
             renderTask.promise.then(function() {
                 pageRendering = false;
                 if (pageNumPending !== null) {
                     // New page rendering is pending
                     renderPage(pageNumPending);
                     pageNumPending = null;
                 }
             });
         });
         // Update page counters
         document.getElementById('page_num').textContent = num;
     }
     /**
      * If another page rendering in progress, waits until the rendering is
      * finised. Otherwise, executes rendering immediately.
      */
     function queueRenderPage(num) {
         if (pageRendering) {
             pageNumPending = num;
         } else {
             renderPage(num);
         }
     }
     /**
      * Displays previous page.
      */
     function onPrevPage() {
         if (pageNum <= 1) {
             return;
         }
         pageNum--;
         queueRenderPage(pageNum);
     }
     document.getElementById('prev').onclick = function(e) {
         e.preventDefault();
         onPrevPage();
     };
     /**
      * Displays next page.
      */
     function onNextPage() {
         if (pageNum >= pdfDoc.numPages) {
             return;
         }
         pageNum++;
         queueRenderPage(pageNum);
     }
     document.getElementById('next').onclick = function(e) {
         e.preventDefault();
         onNextPage();
     };
     /**
      * Asynchronously downloads PDF.
      */
     pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
         pdfDoc = pdfDoc_;
         document.getElementById('page_count').textContent = pdfDoc.numPages;
         // Initial/first page rendering
         renderPage(pageNum);
     });
 }
 // funcion obtiene datos de usuario
 function getDocumentoLab(id_documento) {
     var retrievedObject_td = JSON.parse(localStorage.getItem('Object_td'));
     $('#content').html(template($('#v-spinner').html(), {}));
     fetch('api/laboratorio/resultado', {
         method: "GET",
         headers: {
             "X-REQUEST-ID-DOCUMENTO": id_documento,
             "X-REQUEST-FECHA-DOCUMENTO": retrievedObject_td.fecha,
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-ver-resultado').html(), retrievedObject_td));
         loadPdf(retrievedObject_td.pdf);
     }).catch(function(err) {
         console.error(err);
     });
 }
 // funcion obtiene datos de usuario
 function sendMailDocumentoLab(id_documento) {
     var retrievedObject_td = JSON.parse(localStorage.getItem('Object_td'));
     $('#content').html(template($('#v-spinner').html(), {}));
     fetch('api/laboratorio/resultado', {
         method: "GET",
         headers: {
             "X-REQUEST-ID-DOCUMENTO": id_documento,
             "X-REQUEST-FECHA-DOCUMENTO": retrievedObject_td.fecha,
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $('#content').html(template($('#v-enviar-resultado').html(), retrievedObject_td));
     }).catch(function(err) {
         console.error(err);
     });
 }
 // Updated 28 October 2011: Now allows 0, NaN, false, null and undefined in output. 
 function template(templateid, data) {
     return templateid.replace(/%(\w*)%/g, // or /{(\w*)}/g for "{this} instead of %this%"
         function(m, key) {
             return data.hasOwnProperty(key) ? data[key] : "";
         });
 }

 function MaysPrimera(string) {
     return string.charAt(0).toUpperCase() + string.slice(1);
 }

 function table() {
     $.fn.dataTable.ext.errMode = 'none';
     var table = $('#table').DataTable({
             "ajax": {
                 url: "api/laboratorio/resultados",
                 dataSrc: "customData",
                 serverSide: true,
             },
             responsive: false,
             language: {
                 searchPlaceholder: 'Buscar...',
                 sSearch: '',
                 lengthMenu: '_MENU_ items/página',
                 sProcessing: "Procesando...",
                 sZeroRecords: "No se encontraron resultados",
                 sEmptyTable: "Ningún dato disponible en esta tabla",
                 sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                 sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
                 sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
                 sInfoPostFix: "",
                 sUrl: "",
                 sInfoThousands: ",",
                 sLoadingRecords: "Cargando...",
                 oPaginate: {
                     sFirst: "Primero",
                     sLast: "Último",
                     sNext: "Siguiente",
                     sPrevious: "Anterior"
                 },
                 oAria: {
                     sSortAscending: ": Activar para ordenar la columna de manera ascendente",
                     sSortDescending: ": Activar para ordenar la columna de manera descendente"
                 }
             },
             cache: false,
             columns: [{
                 title: "N°:"
             }, {
                 title: "Fecha:"
             }, {
                 title: "Id Transaccion:"
             }, {
                 title: "Paciente:"
             }, {
                 title: "Convenio:"
             }, {
                 title: "Opciones:"
             }, ],
             aoColumnDefs: [{
                 mRender: function(data, type, row, meta) {
                     return meta.row + meta.settings._iDisplayStart + 1;
                 },
                 visible: true,
                 aTargets: [0]
             }, {
                 mRender: function(data, type, full) {
                     return full.FECHA;
                 },
                 visible: true,
                 aTargets: [1]
             }, {
                 mRender: function(data, type, full) {
                     return full.SC;
                 },
                 visible: true,
                 aTargets: [2]
             }, {
                 mRender: function(data, type, full) {
                     return full.NOMBRE_PERSONA;
                 },
                 visible: true,
                 aTargets: [3]
             }, {
                 mRender: function(data, type, full) {
                     return full.ORIGEN;
                 },
                 visible: true,
                 aTargets: [4]
             }, {
                 mRender: function(data, type, full) {
                     return template($('#btn-table-opc').html(), {
                         id: full.ID_RESULTADO,
                         fecha: full.FECHA,
                         pdf: full.PDF,
                     });
                 },
                 visible: true,
                 aTargets: [5]
             }, ],
             "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {},
             "drawCallback": function(settings) {
                 $('.ver-resultado').click(function(e) {
                     e.stopImmediatePropagation();
                     e.preventDefault();
                     $('#content').html(template($('#v-spinner').html(), {}));
                     var currentRow = $(this).closest("tr");
                     var id_resultado = $(this).attr('id');
                     var fecha = currentRow.find("td:eq(1)").text();
                     var paciente = currentRow.find("td:eq(2)").text();
                     var origen = currentRow.find("td:eq(4)").text();
                     var convenio = currentRow.find("td:eq(5)").text();
                     var pdf = urlhome + 'api/documentos/resultados/' + $(this).attr('id') + '.pdf';
                     var Object_td = {
                         'id_resultado': id_resultado,
                         'fecha': fecha,
                         'origen': origen,
                         'paciente': paciente,
                         'convenio': convenio,
                         'pdf': pdf
                     };
                     // Put the object into storage
                     localStorage.setItem('Object_td', JSON.stringify(Object_td));
                     $('#content').html(template($('#v-ver-resultado').html(), Object_td));
                     loadPdf(pdf);
                     router.navigate('/ver-resultado/' + $(this).attr('id'));
                 });
                 $('.descargar-resultado').click(function(e) {
                     e.stopImmediatePropagation();
                     e.preventDefault();
                     var _td = $(this);
                     _td.attr('disabled', 'disabled');
                     _td.html('Procesando...').addClass('pos-absolute');
                     var currentRow = $(this).closest("tr");
                     var id_resultado = $(this).attr('id');
                     var fecha = currentRow.find("td:eq(1)").text();
                     var paciente = currentRow.find("td:eq(2)").text();
                     var origen = currentRow.find("td:eq(4)").text();
                     var convenio = currentRow.find("td:eq(5)").text();
                     var pdf = urlhome + 'api/documentos/resultados/' + $(this).attr('id') + '.pdf';
                     var Object_td = {
                         'id_resultado': id_resultado,
                         'fecha': fecha,
                         'origen': origen,
                         'paciente': paciente,
                         'convenio': convenio,
                         'pdf': pdf
                     };
                     // Put the object into storage
                     localStorage.setItem('Object_td', JSON.stringify(Object_td));
                     var retrievedObject_td = JSON.parse(localStorage.getItem('Object_td'));
                     fetch('api/laboratorio/resultado', {
                         method: "GET",
                         headers: {
                             "X-REQUEST-ID-DOCUMENTO": id_resultado,
                             "X-REQUEST-FECHA-DOCUMENTO": retrievedObject_td.fecha,
                         },
                     }).then(function(response) {
                         _td.html('<i class="icon ion-md-download"></i> Descargar');
                         _td.removeAttr('disabled').removeClass('pos-absolute');
                         return response.json();
                     }).then(function(data) {
                         console.log('data = ', data);
                         var link = document.createElement('a');
                         link.href = data.pdf;
                         link.download = "Res_Lab_" + id_resultado + " _" + retrievedObject_td.fecha + "_.pdf";
                         link.click();
                     }).catch(function(err) {
                         alert(err);
                         console.error(err);
                     });
                 });
                 $('.enviar-resultado').click(function(e) {
                     e.stopImmediatePropagation();
                     e.preventDefault();
                     var currentRow = $(this).closest("tr");
                     var id_resultado = $(this).attr('id');
                     var fecha = currentRow.find("td:eq(1)").text();
                     var paciente = currentRow.find("td:eq(2)").text();
                     var origen = currentRow.find("td:eq(4)").text();
                     var convenio = currentRow.find("td:eq(5)").text();
                     var pdf = urlhome + 'api/documentos/resultados/' + $(this).attr('id') + '.pdf';
                     var Object_td = {
                         'id_resultado': id_resultado,
                         'fecha': fecha,
                         'origen': origen,
                         'paciente': paciente,
                         'convenio': convenio,
                         'pdf': pdf
                     };
                     // Put the object into storage
                     localStorage.setItem('Object_td', JSON.stringify(Object_td));
                     var retrievedObject_td = JSON.parse(localStorage.getItem('Object_td'));
                     $('#content').html(template($('#v-enviar-resultado').html(), Object_td));
                     router.navigate('/enviar-resultado/' + $(this).attr('id'));
                 });
             },
             "order": [
                 [0, 'Desc']
             ],
         })
         /**
          * Event:       xhrErr.liveAjax
          * Description: Triggered for any and all errors encountered during an XHR request (Meaning it covers
          *              all of the xhrErr*.liveAjax events below)
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErr.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErr', 'General XHR Error: ' + thrown);
         })
         /**
          * Event:       xhrErrTimeout.liveAjax
          * Description: Triggered when a 'timeout' error was thrown from an XHR request
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrTimeout.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErrTimeout', 'XHR Error: Timeout');
         })
         /**
          * Event:       xhrErrError.liveAjax
          * Description: Triggered when a 'error' error was thrown from an XHR request
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrError.liveAjax', function(e, settings, xhr, thrown) {
             console.log('XHR Error: Error');
         })
         /**
          * Event:       xhrErrAbort.liveAjax
          * Description: Triggered when an 'abort' error was thrown from an XHR request
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrAbort.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErrAbort', 'XHR Error: Abort');
         })
         /**
          * Event:       xhrErrParseerror.liveAjax
          * Description: Triggered when a 'parsererror' error was thrown from an XHR request
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrParseerror.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErrParseerror', 'XHR Error: Parse Error');
         })
         /**
          * Event:       xhrErrUnknown.liveAjax
          * Description: Triggered when an unknown error was thrown from an XHR request, this shouldn't ever
          *              happen actually, seeing as how all the textStatus values from
          *              http://api.jquery.com/jquery.ajax/ were accounted for. But I just liked having a default
          *              failsafe, in the case maybe a new error type gets implemented and this plugin doesn't get
          *              updated
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrUnknown.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErrParseerror', '(Unknown) XHR Error: ' + thrown);
         })
         /**
          * Event:       xhrSkipped.liveAjax
          * Description: Triggered when an XHR iteration is skipped, either due to polling being paused, or an XHR request is already processing
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Reason for skip (either 'paused' or 'processing')
          */
         .on('xhrSkipped.liveAjax', function(e, settings, reason) {
             console.log('xhrSkipped', 'XHR Skipped because liveAjax is ' + reason);
         })
         /**
          * Event:       setInterval.liveAjax
          * Description: Triggered when the setTimeout interval has been changed
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('setInterval.liveAjax', function(e, settings, interval) {
             console.log('setInterval', 'XHR polling interval set to ' + interval);
         })
         /**
          * Event:       init.liveAjax
          * Description: Triggered when the liveAjax plugin has been initialized
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('init.liveAjax', function(e, settings, xhr) {
             console.log('init', 'liveAjax initiated');
         })
         /**
          * Event:       clearTimeout.liveAjax
          * Description: Triggered when the timeout has been cleared, killing the XHR polling
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('clearTimeout.liveAjax', function(e, settings, xhr) {
             console.log('clearTimeout', 'liveAjax timeout cleared');
         })
         /**
          * Event:       abortXhr.liveAjax
          * Description: Triggered when the current XHR request was aborted, either by an API method or an internal reason (Not the same as 'xhrErrAbort.liveAjax')
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('abortXhr.liveAjax', function(e, settings, xhr) {
             console.log('abortXhr', 'liveAjax XHR request was aborted');
         })
         /**
          * Event:       setPause.liveAjax
          * Description: Triggered when the liveAjax XHR polling was paused or un-paused
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('setPause.liveAjax', function(e, settings, paused) {
             console.log('setPause', 'liveAjax XHR polling was ' + (paused === true ? 'paused' : 'un-paused'));
         })
         /**
          * Event:       onUpdate.liveAjax
          * Description: Triggered when liveAjax is finished comparing the new/existing JSON, and has implemented any changes to the table, according to the new JSON data
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} Updates that were implemented; {object} New JSON data for tabke; {object} XHR Object
          */
         .on('onUpdate.liveAjax', function(e, settings, updates, json, xhr) {
             console.log('onUpdate', 'JSON Processed - Table updated with new data; ' + (updates.delete.length || 0) + ' deletes, ' + (updates.create.length || 0) + ' additions, ' + Object.keys(updates.update).length + ' updates');
         })
         /**
          * Event:       noUpdate.liveAjax
          * Description: Triggered when liveAjax is finished comparing the new/existing JSON, and no updates were implemented
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} New JSON data for tabke; {object} XHR Object
          */
         .on('noUpdate.liveAjax', function(e, settings, json, xhr) {
             console.log('noUpdate', 'JSON Processed - Table not updated, no new data');
         });
     $('.dataTables_length select').select2({
         minimumResultsForSearch: Infinity
     });
     return table;
 }