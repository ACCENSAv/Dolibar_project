$(document).ready(function () {
    rcmail.register_command('plugin.save2dolibarr', function () {
        var uid = rcmail.message_list.get_selection()[0];
        var mbox = rcmail.env.mailbox;

        if (!uid || !mbox) {
            rcmail.display_message("Paramètres manquants (UID ou dossier).", 'error');
            return;
        }

        window.save2dolibarr_current_uid = uid;
        window.save2dolibarr_current_mbox = mbox;
        $('#save2dolibarr_overlay').show();
        $('#save2dolibarr_modal').show();
        $('body').css('overflow', 'hidden');

        $('#sender_info').html('Recherche en cours...');
        $('#target_inputs_container').empty();
        $('#target_suggestions').empty().hide();
        $('#active_modules_checkbox_container').html('<div style="text-align: center; padding: 20px;">Chargement des modules...</div>'); // Show loading

        $.ajax({
            url: './?_task=mail&_action=plugin.get_active_dolibarr_modules',
            type: 'GET',
            dataType: 'json',
            success: function (activeModules) {
                var container = $('#active_modules_checkbox_container');
                container.empty();

                if (Array.isArray(activeModules) && activeModules.length > 0) {
                    activeModules.forEach(function (module) {
                        var html = `
                            <label style="display:flex; align-items:center; margin-bottom:10px; cursor:pointer;">
                                <input type="checkbox" name="allowed_target_types" value="${module.value}" style="margin-right:10px; width:16px; height:16px;">
                                ${module.label}
                            </label>
                        `;
                        container.append(html);
                    });
                    $('input[name="allowed_target_types"]').off('change').on('change', handleModuleCheckboxChange);

                    getSenderEmailAndCheckDolibarr(uid, mbox);

                } else {
                    container.html('<div style="padding:10px; color:#d32f2f;">Aucun module actif trouvé ou erreur de configuration Dolibarr.</div>');
                    getSenderEmailAndCheckDolibarr(uid, mbox);
                }
            },
            error: function () {
                $('#active_modules_checkbox_container').html('<div style="padding:10px; color:#d32f2f;">Erreur lors du chargement des modules actifs.</div>');
                getSenderEmailAndCheckDolibarr(uid, mbox);
            }
        });

    }, true);
    let checkSenderUrl = rcmail.env.save2dolibarr_check_sender_url;

    function getSenderEmailAndCheckDolibarr(uid, mbox) {
        $.ajax({
            url: './?_task=mail&_action=plugin.get_sender_email',
            type: 'POST',
            data: { _uid: uid, _mbox: mbox },
            dataType: 'json',
            success: function (response) {
                if (response.email) {
                    $.ajax({
                        url: checkSenderUrl,
                        type: 'POST',
                        data: { email: response.email },
                        dataType: 'json',
                        success: function (sender_response) {
                            if (sender_response.found) {
                                $('#sender_info').html('<strong>Tiers trouvé :</strong> ' + sender_response.name);
                                var thirdpartyCheckbox = $('input[name="allowed_target_types"][value="thirdparty"]');
                                if (thirdpartyCheckbox.length && thirdpartyCheckbox.is(':checked')) {
                                    thirdpartyCheckbox.prop('checked', true).trigger('change');
                                    setTimeout(() => {
                                        $('#target_input_thirdparty').val(sender_response.name);
                                        $('#target_input_thirdparty').data('selected-id', sender_response.id);
                                    }, 100);
                                } else if (thirdpartyCheckbox.length) {
                                    thirdpartyCheckbox.prop('checked', true).trigger('change');
                                     setTimeout(() => {
                                        $('#target_input_thirdparty').val(sender_response.name);
                                        $('#target_input_thirdparty').data('selected-id', sender_response.id); 
                                    }, 100);
                                } else {
                                     $('#sender_info').append('<br><small style="color:#ffa000;">(Le module "Tiers" n\'est pas actif pour le pré-remplissage automatique)</small>');
                                }

                            } else {
                                $('#sender_info').html('<strong>Expéditeur inconnu :</strong> ' + response.email);
                            }
                        },
                        error: function () {
                            $('#sender_info').html('<span style="color:#d32f2f;">Erreur lors de la vérification du tiers</span>');
                        }
                    });
                } else {
                    $('#sender_info').html('<span style="color:#d32f2f;">Email expéditeur non trouvé</span>');
                }
            },
            error: function () {
                $('#sender_info').html('<span style="color:#d32f2f;">Erreur lors de la récupération des infos du mail</span>');
            }
        });
    }

    $('#save2dolibarr_close, #save2dolibarr_overlay').click(function () {
        closeModal();
    });

    
    function handleModuleCheckboxChange() {
        var selected = [];
        $('input[name="allowed_target_types"]:checked').each(function() {
            selected.push($(this).val());
        });
        
        var container = $('#target_inputs_container');
        container.empty();
        $('#target_suggestions').empty().hide();
        const moduleLabels = {
            'thirdparty': 'Tiers',
            'contact': 'Contact',
            'project': 'Projet / Opportunité',
            'propal': 'Proposition commerciale',
            'commande': 'Commande client',
            'expedition': 'Expédition',
            'contract': 'Contrat',
            'fichinter': 'Intervention',
            'ticket': 'Ticket',
            'partnership': 'Partenariat',
            'supplier_proposal': 'Proposition fournisseur',
            'supplier_order': 'Commande fournisseur',
            'supplier_invoice': 'Facture fournisseur',
            'reception': 'Réception',
            'invoice': 'Facture client',
            'salary': 'Salaire',
            'loan': 'Emprunt',
            'don': 'Don',
            'holiday': 'Congé',
            'expensereport': 'Note de frais',
            'user': 'Utilisateur',
            'usergroup': 'Groupe',
            'adherent': 'Adhérent',
            'event': 'Agenda / Événement',
            'accounting': 'Comptabilité',
            'affaire': 'Affaire'
        };


        selected.forEach(function (module) {
            var label = moduleLabels[module] || (module.charAt(0).toUpperCase() + module.slice(1));
            var inputId = 'target_input_' + module;
            var html = `
                <div style="margin-bottom:15px;">
                    <label for="${inputId}" style="display:block; font-weight:bold; margin-bottom:5px; color:#444;">${label} :</label>
                    <input type="text" id="${inputId}" data-module="${module}" 
                           style="width:100%; padding:10px; font-size:14px; border:1px solid #ddd; border-radius:4px;" 
                           placeholder="Rechercher un ${label}...">
                </div>
            `;
            container.append(html);
        });
    }
    $('input[name="allowed_target_types"]').on('change', handleModuleCheckboxChange);
    $(document).on('input', '#target_inputs_container input[type=text]', function () {
        var $input = $(this);
        var query = $input.val().trim();
        var module = $input.data('module');

        if (query.length < 2) {
            $('#target_suggestions').hide().empty();
            return;
        }
        $('#target_suggestions').html('<div style="padding:10px; text-align:center; color:#666;">Recherche en cours...</div>').show();

        $.ajax({
            url: './?_task=mail&_action=plugin.save2dolibarr_search_targets',
            method: 'GET',
            data: { q: query, type: module },
            success: function (data) {
                $('#target_suggestions').empty();
                
                if (Array.isArray(data)) {
                    if (data.length > 0) {
                        data.forEach(function (item) {
                            var suggestion = $('<div>')
                                .html('<strong>' + item.label + '</strong><br><small>ID: ' + item.id + '</small>')
                                .css({ 
                                    cursor: 'pointer', 
                                    padding: '10px',
                                    borderBottom: '1px solid #eee',
                                    transition: 'background 0.2s'
                                })
                                .hover(
                                    function() { $(this).css('background', '#f0f0f0'); },
                                    function() { $(this).css('background', ''); }
                                )
                                .on('click', function () {
                                    $input.val(item.label);
                                    $input.data('selected-id', item.id);
                                    $('#target_suggestions').hide();
                                });
                            $('#target_suggestions').append(suggestion);
                        });
                    } else {
                        $('#target_suggestions').html('<div style="padding:10px; color:#666;">Aucun résultat trouvé</div>');
                    }
                } else {
                    $('#target_suggestions').html('<div style="padding:10px; color:#d32f2f;">Erreur de format de réponse</div>');
                }
            },
            error: function () {
                $('#target_suggestions').html('<div style="padding:10px; color:#d32f2f;">Erreur lors de la recherche</div>');
            }
        });
    });

    $('#save2dolibarr_submit').click(function () {
        var selectedModules = [];
        $('input[name="allowed_target_types"]:checked').each(function() {
            selectedModules.push($(this).val());
        });
        
        var targets = [];
        var hasErrors = false;

        selectedModules.forEach(function (module) {
            var input = $('#target_input_' + module);
            var val = input.val().trim();
            var selectedId = input.data('selected-id');

            if (!val || !selectedId) {
                input.css('border-color', '#d32f2f');
                hasErrors = true;
            } else {
                input.css('border-color', '');
                targets.push({ type: module, id: selectedId });
            }
        });

        if (hasErrors || targets.length === 0) {
            rcmail.display_message("Veuillez sélectionner et valider au moins une cible pour les modules choisis.", 'error');
            return;
        }

        closeModal();
        
        rcmail.http_post('plugin.save2dolibarr', {
            _uid: window.save2dolibarr_current_uid,
            _mbox: window.save2dolibarr_current_mbox,
            _links: JSON.stringify(targets)
        }, { 
            timeout: 60000,
            show_message: true
        });
    });

 
    $('#save2dolibarr_submit_only').click(function () {
        closeModal();
        
        rcmail.http_post('plugin.save2dolibarr', {
            _uid: window.save2dolibarr_current_uid,
            _mbox: window.save2dolibarr_current_mbox,
            _links: JSON.stringify([])
        }, { 
            timeout: 60000,
            show_message: true
        });
    });
    function closeModal() {
        $('#save2dolibarr_overlay').hide();
        $('#save2dolibarr_modal').hide();
        $('body').css('overflow', '');
    }
    $(document).keyup(function(e) {
        if (e.key === "Escape" && $('#save2dolibarr_modal').is(':visible')) {
            closeModal();
        }
    });
});