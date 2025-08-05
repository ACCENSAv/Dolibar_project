<?php
class ActionsRedirectmail
{
    /**
     * Masque le bouton "Envoyer un mail" sur la fiche tiers, projet et produit
     */
    public function addHtmlHeader($parameters, &$object, &$action, $hookmanager)
    {
        echo "<!-- Hook addHtmlHeader exécuté -->";

        if (in_array('thirdpartycard', explode(':', $parameters['context']))) {
            echo "<!-- Dans thirdpartycard -->";
            echo '<style>
                a.butAction[href*="action=presend"] {
                    display: none !important;
                }
            </style>';
        }

        if (in_array('projectcard', explode(':', $parameters['context']))) {
            echo "<!-- Dans projectcard -->";
            echo '<style>
                a.butAction[href*="action=presend"] {
                    display: none !important;
                }
            </style>';
        }

        if (in_array('productcard', explode(':', $parameters['context']))) {
            echo "<!-- Dans productcard -->";
            echo '<style>
                a.butAction[href*="action=presend"] {
                    display: none !important;
                }
            </style>';
        }

        return 0;
    }

    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf, $user;

        if ($action === 'edit' || $action === 'presend') {
            return 0;
        }

        if (in_array('thirdpartycard', explode(':', $parameters['context']))) {
            $langs->load("redirectmail@redirectmail");
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/redirectmail/redirect.php?socid='.$object->id.'">';
            print $langs->trans("RedirectToRoundcube");
            print '</a>';
        }

        if (in_array('projectcard', explode(':', $parameters['context']))) {
            $langs->load("redirectmail@redirectmail");
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/redirectmail/redirect.php?card='.$object->id.'">';
            print $langs->trans("RedirectToRoundcube");
            print '</a>';
        }
        if (in_array('productcard', explode(':', $parameters['context']))) {
            $langs->load("redirectmail@redirectmail");
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/redirectmail/redirect.php?card='.$object->id.'">';
            print $langs->trans("RedirectToRoundcube");
            print '</a>';
        }

        return 0;
    }
}
