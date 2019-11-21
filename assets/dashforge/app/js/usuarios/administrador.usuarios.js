 $(function() {
     fetch('api/', {
         method: "GET",
         headers: {
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
     $("#nuevo-usuario").click(function() {
         router.navigate('/nuevo-usuario');
     });
 });
 // Rutas de la pagina
 function route() {
     router.on({
         'nuevo-usuario': function() {
             getNuevoUsuario();
         },
         'ver-usuario/:id': function(params) {
             getUsuario(params.id);
         },
         'editar-usuario/:id': function(params) {
             getUsuario(params.id);
         },
         'eliminar-usuario/:id': function(params) {
             getUsuario(params.id);
         },
         '*': function() {
             table();
         }
     }).resolve();
 }

 function _ini_new_user() {
     $('#response').removeClass('alert-danger');
     $('#response').removeClass('alert-warning');
     $('#response').addClass('alert-warning');
     $("#response").html('Procesando...');
     $('#response').removeClass('d-none');
     $('#btn-nuevo-usuario').attr('disabled', true);
     fetch('api/perfil', {
         method: "POST",
         body: $('#nuevousuario_mx').serialize(),
         headers: {
             "Content-Type": "application/x-www-form-urlencoded",
             "X-REQUEST-TRACK": "new"
         },
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         if (data.success) {
             $('#response').html(data.message).css('font-weight', 'bold');
             $("#response").removeClass('alert-warning');
             $("#response").addClass('alert-success');
             $('#btn-nuevo-usuario').removeAttr('disabled');
             $('#nuevousuario_mx').trigger("reset");
             window.location.assign(urlhome + 'usuarios');
         } else {
             $('#response').html(data.message).css('font-weight', 'bold');
             $("#response").removeClass('alert-warning');
             $("#response").addClass('alert-danger');
             $('#btn-nuevo-usuario').removeAttr('disabled');
         }
     }).catch(function(err) {
         console.error(err);
     });
 };

 function getNuevoUsuario() {
     $('#nuevo-usuario').remove();
     $('#active-page').html('Nuevo Usuario');
     $('#content').html(template($('#v-nuevo-usuario').html(), {}));
     getModulos();
     $("#btn-nuevo-usuario").click(function() {
         _ini_new_user();
     });
 }

 function getModulos() {
     fetch('api/modulos', {
         method: "GET",
     }).then(function(response) {
         return response.json();
     }).then(function(data) {
         console.log('data = ', data);
         $.each(data, function(index, value) {
             if ((index + 1) == 1) {
                 $('#modulos').append(template($('#v-modulos').html(), {
                     val: value.val,
                     class: 'mg-t-10',
                 }));
             } else {
                 $('#modulos').append(template($('#v-modulos').html(), {
                     val: value.val,
                     class: 'mg-sm-l-30 mg-t-10',
                 }));
             }
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
         $('#content').html(template($('#v-usuario').html(), {
             user: data.customData.user,
             email: data.customData.email,
             id: data.customData.id,
         }));
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
     $('#active-page').html('Ver Usuarios');
     $('#content').html(template($('#v-table').html(), {}));
     $.fn.dataTable.ext.errMode = 'none';
     var table = $('#table').DataTable({
             "ajax": {
                 url: "api/usuarios",
                 dataSrc: "customData",
                 serverSide: true,
             },
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
                 title: "Usuario:"
             }, {
                 title: "Correo:"
             }, {
                 title: "Status:"
             }, {
                 title: "Rol:"
             }, {
                 title: "Convenio:"
             }, {
                 title: "Ultima Interacción:"
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
                     return full.user;
                 },
                 visible: true,
                 aTargets: [1]
             }, {
                 mRender: function(data, type, full) {
                     return full.email;
                 },
                 visible: true,
                 aTargets: [2]
             }, {
                 mRender: function(data, type, full) {
                     if (full.status) {
                         return 'Activo';
                     } else {
                         return 'No Activo';
                     }
                 },
                 visible: true,
                 aTargets: [3]
             }, {
                 mRender: function(data, type, full) {
                     return full.name_rol;
                 },
                 visible: true,
                 aTargets: [4]
             }, {
                 mRender: function(data, type, full) {
                     return full.name_convenio;
                 },
                 visible: true,
                 aTargets: [5]
             }, {
                 mRender: function(data, type, full) {
                     return full.last_session;
                 },
                 visible: true,
                 aTargets: [6]
             }, {
                 mRender: function(data, type, full) {
                     return template($('#btn-table-opc').html(), {
                         id: full.id
                     });
                 },
                 visible: true,
                 aTargets: [7]
             }, ],
             "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {},
             "drawCallback": function(settings) {
                 $('.ver').click(function(e) {
                     e.preventDefault();
                     router.resolve(urlhome + 'perfil#!/' + $(this).attr('id'));
                     window.location.assign('perfil#!/' + $(this).attr('id'));
                 });
             },
             "order": [
                 [0, 'Desc']
             ],
             rowId: 'id',
             liveAjax: {
                 // 2 second interval
                 interval: 1100,
                 // Do _not_ fire the DT callbacks for every XHR request made by liveAjax
                 dtCallbacks: false,
                 // Abort the XHR polling if one of the below errors were encountered
                 abortOn: ['error', 'timeout', 'parsererror'],
                 // Disable pagination resetting on updates ("true" will send the viewer
                 // to the first page every update)
                 resetPaging: false
             }
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