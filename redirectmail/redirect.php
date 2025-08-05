<?php
// Chargement de l'environnement Dolibarr
require '../../main.inc.php';

// Chargement des classes nécessaires
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
// Inclusion de la classe Form pour les fonctions d'affichage HTML (utilisée dans la fiche projet)
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
// Avant la ligne 242, avec les autres inclusions
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

// Vérification si le module Projet est activé avant d'inclure ses fichiers
$project_enabled = isModEnabled('project');
if ($project_enabled) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    // Inclusion fondamentale de la bibliothèque de fonctions des projets
    // Ce fichier contient 'project_prepare_head' et est essentiel pour l'ergonomie de la fiche projet.
    // Il doit se trouver à : [RACINE_DOLIBARR]/htdocs/core/lib/project.lib.php
    require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
}

$formcompany = new FormCompany($db);
// Chargement des traductions
$langs->load("companies");
if ($project_enabled) {
    $langs->load("projects");
}
$langs->load("other"); // Peut être utile pour certains éléments comme 'Other attributes'

// Instanciation de la classe Form (nécessaire pour le rendu des extrafields, catégories, etc.)
$form = new Form($db);

// Récupération des paramètres
$socid = GETPOST('socid', 'int');
$projectid = GETPOST('card', 'int');
$email = '';
$object = null;
$context = '';

// Cas 1 : Gestion des sociétés (tiers)
if ($socid > 0) {
    $object = new Societe($db);
    if ($object->fetch($socid)) {
        $context = 'societe';
        $email = $object->email;
    }
}
// Cas 2 : Gestion des projets (seulement si module activé)
elseif ($projectid > 0 && $project_enabled) {
    $object = new Project($db);
    if ($object->fetch($projectid)) {
        $context = 'project';

        // Charger l'objet tiers associé au projet si ce n'est pas déjà fait
        if (!empty($object->socid) && (empty($object->thirdparty) || !is_object($object->thirdparty) || empty($object->thirdparty->id))) {
            $customer_obj = new Societe($db);
            if ($customer_obj->fetch($object->socid)) {
                $object->thirdparty = $customer_obj;
            }
        }
        
        // Récupérer l'e-mail du tiers associé au projet
        // Cette variable sera vide si le projet n'a pas de tiers ou si le tiers n'a pas d'e-mail.
        // Roundcube s'ouvrira quand même pour les projets.
        $email = ''; // Initialiser $email à vide par sécurité
        if (isset($object->thirdparty) && is_object($object->thirdparty) && !empty($object->thirdparty->email)) {
            $email = $object->thirdparty->email;
        }

        /*
        // --- DÉBUT DES LIGNES DE DÉBOGAGE (COMMENTÉES POUR LE CODE FINAL) ---
        echo "\n\n";
        echo "\n";
        echo "\n";
        
        if (isset($object->thirdparty) && is_object($object->thirdparty)) {
            echo "\n";
            echo "\n";
        } else {
            echo "\n";
        }
        echo "\n";
        echo "\n\n";
        // --- FIN DES LIGNES DE DÉBOGAGE ---
        */
    }
}

// Affichage de l'interface
if (!empty($object)) {
    // Initialise l'en-tête de la page Dolibarr
    llxHeader('', 'Nouveau Mail', '', '', 0, 0, '', '', '', 'newmail');

    if ($context === 'societe') {
        $head = societe_prepare_head($object);
        print dol_get_fiche_head($head, 'newmail', $langs->trans("ThirdParty"), -1, 'company');
        $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
        dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');
        $reshook = $hookmanager->executeHooks('tabContentViewThirdparty', $parameters, $object, $action);
		if (empty($reshook)) {
			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border tableforfield centpercent">';

			// Type Prospect/Customer/Supplier
			print '<tr><td class="titlefieldmiddle">'.$langs->trans('NatureOfThirdParty').'</td><td>';
			print $object->getTypeUrl(1);
			print '</td></tr>';

			// Prefix
			if (getDolGlobalString('SOCIETE_USEPREFIX')) {  // Old not used prefix field
				print '<tr><td>'.$langs->trans('Prefix').'</td><td>'.dol_escape_htmltag($object->prefix_comm).'</td>';
				print '</tr>';
			}

			// Customer code
			if ($object->client) {
				print '<tr><td>';
				print $langs->trans('CustomerCode');
				print '</td>';
				print '<td>';
				print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
				$tmpcheck = $object->check_codeclient();
				if ($tmpcheck != 0 && $tmpcheck != -5) {
					print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
				}
				print '</td>';
				print '</tr>';
			}

			// Supplier code
			if (((isModEnabled("fournisseur") && $user->hasRight('fournisseur', 'lire') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))) && $object->fournisseur) {
				print '<tr><td>';
				print $langs->trans('SupplierCode').'</td><td>';
				print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
				$tmpcheck = $object->check_codefournisseur();
				if ($tmpcheck != 0 && $tmpcheck != -5) {
					print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
				}
				print '</td>';
				print '</tr>';
			}

			// Barcode
			if (isModEnabled('barcode')) {
				print '<tr><td>';
				print $langs->trans('Gencod').'</td><td>'.showValueWithClipboardCPButton(dol_escape_htmltag($object->barcode));
				print '</td>';
				print '</tr>';
			}

			// Prof ids
			$i = 1;
			$j = 0;
			$NBPROFIDMIN = getDolGlobalInt('THIRDPARTY_MIN_NB_PROF_ID', 2);
			$NBPROFIDMAX = getDolGlobalInt('THIRDPARTY_MAX_NB_PROF_ID', 6);
			while ($i <= $NBPROFIDMAX) {
				$idprof = $langs->transcountry('ProfId'.$i, $object->country_code);
				if (!empty($conf->dol_optimize_smallscreen)) {
					$idprof = $langs->transcountry('ProfId'.$i.'Short', $object->country_code);
				}
				if ($idprof != '-' && ($i <= $NBPROFIDMIN || !empty($langs->tab_translate['ProfId'.$i.$object->country_code]))) {
					print '<tr>';
					print '<td>'.$idprof.'</td><td>';
					$key = 'idprof'.$i;
					print dol_print_profids($object->$key, 'ProfId'.$i, $object->country_code, 1);
					if ($object->$key) {
						if ($object->id_prof_check($i, $object) > 0) {
							if (!empty($object->id_prof_url($i, $object))) {
								print ' &nbsp; '.$object->id_prof_url($i, $object);
							}
						} else {
							print ' <span class="error">('.$langs->trans("ErrorWrongValue").')</span>';
						}
					}
					print '</td>';
					print '</tr>';
					$j++;
				}
				$i++;
			}


			// This fields are used to know VAT to include in an invoice when the thirdparty is making a sale, so when it is a supplier.
			// We don't need them into customer profile.
			// Except for spain and localtax where localtax depends on buyer and not seller

			if ($object->fournisseur) {
				// VAT is used
				print '<tr><td>';
				print $form->textwithpicto($langs->trans('VATIsUsed'), $langs->trans('VATIsUsedWhenSelling'));
				print '</td><td>';
				print yn($object->tva_assuj);
				print '</td>';
				print '</tr>';

				if (getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
					// VAT reverse charge by default
					print '<tr><td>';
					print $form->textwithpicto($langs->trans('VATReverseChargeByDefault'), $langs->trans('VATReverseChargeByDefaultDesc'));
					print '</td><td>';
					print '<input type="checkbox" name="vat_reverse_charge" ' . ($object->vat_reverse_charge == '1' ? ' checked' : '') . ' disabled>';
					print '</td>';
					print '</tr>';
				}
			}

			// Local Taxes
			if ($object->fournisseur || $mysoc->country_code == 'ES') {
				if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1") {
					print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
					print yn($object->localtax1_assuj);
					print '</td></tr><tr><td>'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
					print yn($object->localtax2_assuj);
					print '</td></tr>';

					if ($object->localtax1_assuj == "1" && (!isOnlyOneLocalTax(1))) {
						print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
						print '<input type="hidden" name="action" value="set_localtax1">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
						print '<tr><td>'.$langs->transcountry("Localtax1", $mysoc->country_code).' <a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editRE&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</td>';
						if ($action == 'editRE') {
							print '<td class="left">';
							$formcompany->select_localtax(1, (float) $object->localtax1_value, "lt1");
							print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
						} else {
							print '<td>'.$object->localtax1_value.'</td>';
						}
						print '</tr></form>';
					}
					if ($object->localtax2_assuj == "1" && (!isOnlyOneLocalTax(2))) {
						print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
						print '<input type="hidden" name="action" value="set_localtax2">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
						print '<tr><td>'.$langs->transcountry("Localtax2", $mysoc->country_code).'<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editIRPF&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</td>';
						if ($action == 'editIRPF') {
							print '<td class="left">';
							$formcompany->select_localtax(2, (float) $object->localtax2_value, "lt2");
							print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
						} else {
							print '<td>'.$object->localtax2_value.'</td>';
						}
						print '</tr></form>';
					}
				} elseif ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj != "1") {
					print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
					print yn($object->localtax1_assuj);
					print '</td></tr>';
					if ($object->localtax1_assuj == "1" && (!isOnlyOneLocalTax(1))) {
						print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
						print '<input type="hidden" name="action" value="set_localtax1">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
						print '<tr><td> '.$langs->transcountry("Localtax1", $mysoc->country_code).'<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editRE&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</td>';
						if ($action == 'editRE') {
							print '<td class="left">';
							$formcompany->select_localtax(1, (float) $object->localtax1_value, "lt1");
							print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
						} else {
							print '<td>'.$object->localtax1_value.'</td>';
						}
						print '</tr></form>';
					}
				} elseif ($mysoc->localtax2_assuj == "1" && $mysoc->localtax1_assuj != "1") {
					print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
					print yn($object->localtax2_assuj);
					print '</td></tr>';
					if ($object->localtax2_assuj == "1" && (!isOnlyOneLocalTax(2))) {
						print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
						print '<input type="hidden" name="action" value="set_localtax2">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
						print '<tr><td> '.$langs->transcountry("Localtax2", $mysoc->country_code).' <a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editIRPF&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</td>';
						if ($action == 'editIRPF') {
							print '<td class="left">';
							$formcompany->select_localtax(2, (float) $object->localtax2_value, "lt2");
							print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
						} else {
							print '<td>'.$object->localtax2_value.'</td>';
						}
						print '</tr></form>';
					}
				}
			}

			// Sale tax code (VAT code)
			print '<tr>';
			print '<td class="nowrap">'.$langs->trans('VATIntra').'</td><td>';
			if ($object->tva_intra) {
				$s = '';
				$s .= dol_print_profids($object->tva_intra, 'VAT', $object->country_code, 1);
				$s .= '<input type="hidden" id="tva_intra" name="tva_intra" maxlength="20" value="'.$object->tva_intra.'">';

				if (!getDolGlobalString('MAIN_DISABLEVATCHECK') && isInEEC($object)) {
					$s .= ' &nbsp; ';

					if ($conf->use_javascript_ajax) {
						$widthpopup = 600;
						if (!empty($conf->dol_use_jmobile)) {
							$widthpopup = 350;
						}
						$heightpopup = 400;
						print "\n";
						print '<script type="text/javascript">';
						print "function CheckVAT(a) {\n";
						if ($mysoc->country_code == 'GR' && $object->country_code == 'GR' && !empty($u)) {
							print "GRVAT(a,'{$u}','{$p}','{$myafm}');\n";
						} else {
							print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a, '".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."', ".$widthpopup.", ".$heightpopup.");\n";
						}
						print "}\n";
						print '</script>';
						print "\n";
						$s .= '<a href="#" class="hideonsmartphone" onclick="CheckVAT(jQuery(\'#tva_intra\').val());">'.$langs->trans("VATIntraCheck").'</a>';
						$s = $form->textwithpicto($s, $langs->trans("VATIntraCheckDesc", $langs->transnoentitiesnoconv("VATIntraCheck")), 1);
					} else {
						$s .= '<a href="'.$langs->transcountry("VATIntraCheckURL", (string) $object->country_id).'" class="hideonsmartphone" target="_blank" rel="noopener noreferrer">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"), 'help').'</a>';
					}
				}
				print $s;
			} else {
				print '&nbsp;';
			}
			print '</td></tr>';

			// Warehouse
			if (isModEnabled('stock') && getDolGlobalString('SOCIETE_ASK_FOR_WAREHOUSE')) {
				$langs->load('stocks');
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				print '<tr class="nowrap">';
				print '<td>';
				print $form->editfieldkey("Warehouse", 'warehouse', '', $object, $user->hasRight('societe', 'creer'));
				print '</td><td>';
				if ($action == 'editwarehouse') {
					$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_warehouse, 'fk_warehouse', 1);
				} else {
					if ($object->fk_warehouse > 0) {
						print img_picto('', 'stock', 'class="paddingrightonly"');
					}
					$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_warehouse, 'none');
				}
				print '</td>';
				print '</tr>';
			}

			print '</table>';
			print '</div>';

			print '<div class="fichehalfright">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border tableforfield centpercent">';

			// Tags / categories
			if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
				// Customer
				if ($object->prospect || $object->client || getDolGlobalString('THIRDPARTY_CAN_HAVE_CUSTOMER_CATEGORY_EVEN_IF_NOT_CUSTOMER_PROSPECT')) {
					print '<tr><td class="titlefieldmiddle">'.$langs->trans("CustomersCategoriesShort").'</td>';
					print '<td>';
					print $form->showCategories($object->id, Categorie::TYPE_CUSTOMER, 1);
					print "</td></tr>";
				}

				// Supplier
				if (((isModEnabled("fournisseur") && $user->hasRight('fournisseur', 'lire') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))) && $object->fournisseur) {
					print '<tr><td class="titlefieldmiddle">'.$langs->trans("SuppliersCategoriesShort").'</td>';
					print '<td>';
					print $form->showCategories($object->id, Categorie::TYPE_SUPPLIER, 1);
					print "</td></tr>";
				}
			}


			// Third-Party Type
			print '<tr><td class="titlefieldmiddle">';
			print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans('ThirdPartyType').'</td>';
			if ($action != 'editthirdpartytype' && $user->hasRight('societe', 'creer')) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editthirdpartytype&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td>';
			$html_name = ($action == 'editthirdpartytype') ? 'typent_id' : 'none';
			$formcompany->formThirdpartyType($_SERVER['PHP_SELF'].'?socid='.$object->id, (string) $object->typent_id, $html_name, '');
			print '</td></tr>';

			// Workforce/Staff
			if (!getDolGlobalString('SOCIETE_DISABLE_WORKFORCE')) {
				print '<tr><td>'.$langs->trans("Workforce").'</td><td>'.$object->effectif.'</td></tr>';
			}

			// Legal
			print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td>'.dolPrintHTML($object->forme_juridique).'</td></tr>';

			// Capital
			print '<tr><td>'.$langs->trans('Capital').'</td><td>';
			if ($object->capital) {
				if (isModEnabled("multicurrency") && !empty($object->multicurrency_code)) {
					print price($object->capital, 0, $langs, 0, -1, -1, $object->multicurrency_code);
				} else {
					print price($object->capital, 0, $langs, 0, -1, -1, $conf->currency);
				}
			} else {
				print '&nbsp;';
			}
			print '</td></tr>';

			// Unsubscribe opt-out
			if (isModEnabled('mailing')) {
				$result = $object->getNoEmail();
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
				print '<tr><td>'.$langs->trans("No_Email").'</td><td>';
				if ($object->email) {
					print yn($object->no_email);
				} else {
					$langs->load("mails");
					print '<span class="opacitymedium">'.$langs->trans("EMailNotDefined").'</span>';
				}

				$langs->load("mails");
				print ' &nbsp; <span class="badge badge-secondary" title="'.dol_escape_htmltag($langs->trans("NbOfEMailingsSend")).'">'.$object->getNbOfEMailings().'</span>';

				print '</td></tr>';
			}

			// Default language
			if (getDolGlobalInt('MAIN_MULTILANGS')) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				print '<tr><td>'.$langs->trans("DefaultLang").'</td><td>';
				//$s=picto_from_langcode($object->default_lang);
				//print ($s?$s.' ':'');
				$langs->load("languages");
				$labellang = ($object->default_lang ? $langs->trans('Language_'.$object->default_lang) : '');
				print picto_from_langcode($object->default_lang, 'class="paddingrightonly saturatemedium opacitylow"');
				print $labellang;
				print '</td></tr>';
			}

			// Incoterms
			if (isModEnabled('incoterm')) {
				print '<tr><td>';
				print '<table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans('IncotermLabel').'</td>';
				if ($action != 'editincoterm' && $user->hasRight('societe', 'creer')) {
					print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit('', 1).'</a></td>';
				}
				print '</tr></table>';
				print '</td><td colspan="3">';
				if ($action != 'editincoterm') {
					print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
				} else {
					print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?socid='.$object->id);
				}
				print '</td></tr>';
			}

			// Multicurrency
			if (isModEnabled("multicurrency")) {
				print '<tr>';
				print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
				print '<td>';
				print !empty($object->multicurrency_code) ? currency_name($object->multicurrency_code, 1) : '';
				print '</td></tr>';
			}

			if (getDolGlobalString('ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY')) {
				// Accountancy sell code
				print '<tr><td class="nowrap">';
				print $langs->trans("ProductAccountancySellCode");
				print '</td><td colspan="2">';
				if (isModEnabled('accounting')) {
					if (!empty($object->accountancy_code_sell)) {
						$accountingaccount = new AccountingAccount($db);
						$accountingaccount->fetch(0, $object->accountancy_code_sell, 1);

						print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
					}
				} else {
					print $object->accountancy_code_sell;
				}
				print '</td></tr>';

				// Accountancy buy code
				print '<tr><td class="nowrap">';
				print $langs->trans("ProductAccountancyBuyCode");
				print '</td><td colspan="2">';
				if (isModEnabled('accounting')) {
					if (!empty($object->accountancy_code_buy)) {
						$accountingaccount2 = new AccountingAccount($db);
						$accountingaccount2->fetch(0, $object->accountancy_code_buy, 1);

						print $accountingaccount2->getNomUrl(0, 1, 1, '', 1);
					}
				} else {
					print $object->accountancy_code_buy;
				}
				print '</td></tr>';
			}

			// Other attributes
			$parameters = array('socid' => $socid, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			// Parent company
			if (!getDolGlobalString('SOCIETE_DISABLE_PARENTCOMPANY')) {
				print '<tr><td>';
				print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans('ParentCompany').'</td>';
				if ($action != 'editparentcompany' && $user->hasRight('societe', 'creer')) {
					print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editparentcompany&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a></td>';
				}
				print '</tr></table>';
				print '</td><td>';
				$html_name = ($action == 'editparentcompany') ? 'parent_id' : 'none';
				$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$object->id, (string) $object->parent, $html_name, '', 1, 0, 0, array(), 0, array($object->id));
				print '</td></tr>';
			}

			// Sales representative
			include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

			// Module Adherent
			if (isModEnabled('member')) {
				$langs->load("members");
				print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
				print '<td>';
				$adh = new Adherent($db);
				$result = $adh->fetch(0, '', $object->id);
				if ($result > 0) {
					$adh->ref = $adh->getFullName($langs);
					print $adh->getNomUrl(-1);
				} else {
					print '<span class="opacitymedium">'.$langs->trans("ThirdpartyNotLinkedToMember").'</span>';
				}
				print "</td></tr>\n";
			}

			// Link user (you must create a contact to get a user)
			/*
			print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
			if ($object->user_id) {
				$dolibarr_user = new User($db);
				$result = $dolibarr_user->fetch($object->user_id);
				print $dolibarr_user->getLoginUrl(-1);
			} else {
				//print '<span class="opacitymedium">'.$langs->trans("NoDolibarrAccess").'</span>';
				if (!$object->user_id && $user->hasRight('user', 'user', 'creer')) {
					print '<a class="aaa" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=create_user&token='.newToken().'">'.img_picto($langs->trans("CreateDolibarrLogin"), 'add').' '.$langs->trans("CreateDolibarrLogin").'</a>';
				}
			}
			print '</td></tr>';
			*/

			print '</table>';
			print '</div>';

			print '</div>';
			print '<div class="clearboth"></div>';
		}

		print dol_get_fiche_end();
    } elseif ($context === 'project') {
        $head = project_prepare_head($object);
        // Affiche l'en-tête de la fiche projet avec l'onglet "newmail" actif
        print dol_get_fiche_head($head, 'newmail', $langs->trans("Project"), -1, 'project');

        

        // Définition du lien de retour à la liste
        if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['project'])) {
            $tmpurl = $_SESSION['pageforbacktolist']['project'];
            $tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
            $linkback = '<a href="'.$tmpurl.(preg_match('/\?/', $tmpurl) ? '&' : '?'). 'restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
        } else {
            $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
        }

        // Construction du contenu HTML complémentaire pour la bannière (référence et titre du projet)
        $morehtmlref = '<div class="refidno">';
        $morehtmlref .= $object->title; // Titre du projet
        if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
            $morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'project');
        }
        $morehtmlref .= '</div>';

        // Filtre pour la navigation Suivant/Précédent (droits utilisateur)
        if (!$user->hasRight('projet', 'all', 'lire')) {
            $objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
            $object->next_prev_filter = "te.rowid:IN:".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0');
        }

        // Affichage de la bannière Dolibarr standard pour le projet
        dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

        // Début du contenu principal de la fiche projet (blocs d'informations fixes)
        print '<div class="fichecenter">'; // Ce div 'fichecenter' est pour les infos du projet, PAS pour l'iframe mail
        print '<div class="fichehalfleft">';
        print '<div class="underbanner clearboth"></div>';

        print '<table class="border tableforfield centpercent">';

        // Lignes d'informations : Utilisation
        if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') || !getDolGlobalString('PROJECT_HIDE_TASKS') || isModEnabled('eventorganization')) {
            print '<tr><td class="tdtop">';
            print $langs->trans("Usage");
            print '</td>';
            print '<td>';
            if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
                print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_opportunity ? ' checked="checked"' : '')).'"> ';
                $htmltext = $langs->trans("ProjectFollowOpportunity");
                print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
                print '<br>';
            }
            if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
                print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_task ? ' checked="checked"' : '')).'"> ';
                $htmltext = $langs->trans("ProjectFollowTasks");
                print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
                print '<br>';
            }
            if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
                print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_bill_time ? ' checked="checked"' : '')).'"> ';
                $htmltext = $langs->trans("ProjectBillTimeDescription");
                print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
                print '<br>';
            }
            if (isModEnabled('eventorganization')) {
                print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_organize_event ? ' checked="checked"' : '')).'"> ';
                $htmltext = $langs->trans("EventOrganizationDescriptionLong");
                print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
            }
            print '</td></tr>';
        }

        // Lignes d'informations : Statut d'opportunité et probabilité (si module activé)
        if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') && !empty($object->usage_opportunity)) {
            print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
            $code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
            if ($code) {
                print $langs->trans("OppStatus".$code);
            }
            print '</td></tr>';

            print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
            if (!is_null($object->opp_percent) && strcmp($object->opp_percent, '')) {
                print price($object->opp_percent, 0, $langs, 1, 0).' %';
            }
            print '</td></tr>';

            print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
            if (!is_null($object->opp_amount) && strcmp($object->opp_amount, '')) {
                print '<span class="amount">'.price($object->opp_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
                if (strcmp($object->opp_percent, '')) {
                    print ' &nbsp; &nbsp; &nbsp; <span title="'.dol_escape_htmltag($langs->trans('OpportunityWeightedAmount')).'"><span class="opacitymedium">'.$langs->trans("OpportunityWeightedAmountShort").'</span>: <span class="amount">'.price($object->opp_amount * $object->opp_percent / 100, 0, $langs, 1, 0, -1, $conf->currency).'</span></span>';
                }
            }
            print '</td></tr>';
        }

        // Lignes d'informations : Budget
        print '<tr><td>'.$langs->trans("Budget").'</td><td>';
        if (!is_null($object->budget_amount) && strcmp($object->budget_amount, '')) {
            print '<span class="amount">'.price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
        }
        print '</td></tr>';

        // Lignes d'informations : Dates de début et de fin
        print '<tr><td>'.$langs->trans("Dates").'</td><td>';
        $start = dol_print_date($object->date_start, 'day');
        print($start ? $start : '?');
        $end = dol_print_date($object->date_end, 'day');
        print ' - ';
        print($end ? $end : '?');
        if ($object->hasDelay()) {
            print img_warning("Late");
        }
        print '</td></tr>';

        // Lignes d'informations : Visibilité
        print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
        if ($object->public) {
            print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
            print $langs->trans('SharedProject');
        } else {
            print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
            print $langs->trans('PrivateProject');
        }
        print '</td></tr>';

        // Lignes d'informations : Attributs supplémentaires (Extrafields)
        $cols = 2; // Nombre de colonnes pour le template extrafields_view.tpl.php
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

        print '</table>';

        print '</div>'; // Fin fichehalfleft

        // Bloc de droite
        print '<div class="fichehalfright">';
        print '<div class="underbanner clearboth"></div>';

        print '<table class="border tableforfield centpercent">';

        // Lignes d'informations : Catégories
        if (isModEnabled('category')) {
            print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
            print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
            print "</td></tr>";
        }

        // Lignes d'informations : Description
        print '<tr><td class="titlefield'.($object->description ? ' noborderbottom' : '').'" colspan="2">'.$langs->trans("Description").'</td></tr>';
        if ($object->description) {
            print '<tr><td class="nottitleforfield" colspan="2">';
            print '<div class="longmessagecut">';
            print dolPrintHTML($object->description);
            print '</div>';
            print '</td></tr>';
        }

        print '</table>';

        print '</div>'; // Fin fichehalfright
        print '</div>'; // Fin du 'fichecenter' qui contient les informations du projet
        print '<div class="clearboth"></div>'; // Pour s'assurer que les blocs flottants sont bien gérés

        

    } // Fin du bloc elseif ($context === 'project')

    // Ceci est le 'fichecenter' qui contiendra votre formulaire d'envoi d'e-mail (Roundcube)
    // Il commence APRÈS les blocs d'informations fixes du projet.
    print '<div class="fichecenter"><div class="underbanner clearboth"></div>';
    print load_fiche_titre('Envoi d\'un mail'); // Titre pour la section de l'e-mail

    // Pour les projets, ouvrir Roundcube systématiquement.
    // Pour les tiers (societes), si l'email est vide, afficher le message d'erreur.
    if ($context === 'societe' && empty($email)) {
        print '<div class="warning">Ce tiers n\'a pas d\'adresse email.</div>';
    } else {
        // Pour les projets (même si $email est vide) OU pour les sociétés (si $email n'est pas vide),
        // nous construisons et affichons l'iframe Roundcube.
        $roundcube_url = DOL_URL_ROOT.'/custom/roundcubemodule/roundcube/?_autologin=1'
                       . '&_task=mail&_action=compose'
                       . '&to=' . urlencode($email); // Si $email est vide, le champ "À" sera vide dans Roundcube

        print '<iframe
                src="'.$roundcube_url.'"
                style="width:100%; height:700px; border:none;"
                frameborder="0"
                allowfullscreen>
              </iframe>';
    }

    print '</div>'; // Fin du 'fichecenter' pour l'e-mail
    print dol_get_fiche_end(); // Appel unique de cette fonction pour fermer la fiche Dolibarr
    llxFooter(); // Termine le rendu de la page Dolibarr
} else {
    // Message d'erreur adapté au contexte si l'objet n'est pas trouvé
    if ($projectid > 0 && !$project_enabled) {
        setEventMessages("Le module Projet n'est pas activé", null, 'errors');
    }
    dol_print_error($db, $langs->trans("ErrorRecordNotFound"));
}

// Fermeture de la connexion à la base de données
if (!empty($db)) {
    $db->close();
}
?>


