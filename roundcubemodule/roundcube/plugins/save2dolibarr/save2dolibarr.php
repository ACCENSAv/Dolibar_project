<?php

class save2dolibarr extends rcube_plugin
{
    public $task = 'mail';

    private $dolibarr_script_url;
    private $dolibarr_search_target_url;
    private $dolibarr_check_sender_url;
    private $dolibarr_active_modules_url;

    function init()
    {
        $this->load_dolibarr_urls();
        $this->include_script('save2dolibarr.js');
        $this->include_stylesheet('save2dolibarr.css');

        $this->add_button([
            'command' => 'plugin.save2dolibarr',
            'type' => 'link',
            'title' => 'Enregistrer dans Dolibarr',
            'class' => 'button icon save2dolibarr',
            'label' => 'save',
        ], 'toolbar');

        $this->register_action('plugin.save2dolibarr', [$this, 'save_mail']);
        $this->register_action('plugin.save2dolibarr_search_targets', [$this, 'search_targets']);
        $this->register_action('plugin.get_sender_email', [$this, 'get_sender_email']);
        $this->register_action('plugin.get_active_dolibarr_modules', [$this, 'get_active_dolibarr_modules']); 
        $this->add_hook('render_page', [$this, 'inject_modal']);
    }
   private function load_dolibarr_urls()
    {
        $rcmail = rcmail::get_instance();
        $base_url_for_dolibarr_scripts = null; // Cette variable contiendra l'URL de base pour les scripts Dolibarr

        $script_name = $_SERVER['SCRIPT_NAME'];
        $request_scheme = $_SERVER['REQUEST_SCHEME'];
        $http_host = $_SERVER['HTTP_HOST'];

        $rcmail->write_log('plugin', "load_dolibarr_urls: SCRIPT_NAME actuel: " . $script_name);

        // Cas 1 : SCRIPT_NAME contient '/htdocs/', on le conserve
        $htdocs_pos = strpos($script_name, '/htdocs/');
        if ($htdocs_pos !== false) {
            $path_segment_to_htdocs = substr($script_name, 0, $htdocs_pos + strlen('/htdocs/'));
            $base_url_for_dolibarr_scripts = $request_scheme . '://' . $http_host . rtrim($path_segment_to_htdocs, '/');
            $rcmail->write_log('plugin', "load_dolibarr_urls: Cas 1 - htdocs trouvé dans SCRIPT_NAME. Base Dolibarr: " . $base_url_for_dolibarr_scripts);
        } else {
            // Cas 2 : SCRIPT_NAME ne contient PAS '/htdocs/'.
            // On remonte à la racine de Dolibarr, sans ajouter /htdocs
            $roundcube_relative_base = '/custom/roundcubemodule/roundcube/';
            $pos_roundcube_base = strrpos($script_name, $roundcube_relative_base);

            if ($pos_roundcube_base !== false) {
                // Extrait la partie de l'URL juste avant '/custom/roundcubemodule/roundcube/'
                $dolibarr_root_url_segment = substr($script_name, 0, $pos_roundcube_base);
                // On n'ajoute PAS '/htdocs' ici
                $base_url_for_dolibarr_scripts = $request_scheme . '://' . $http_host . rtrim($dolibarr_root_url_segment, '/');
                $rcmail->write_log('plugin', "load_dolibarr_urls: Cas 2 - Roundcube trouvé, PAS d'ajout de /htdocs. Base Dolibarr: " . $base_url_for_dolibarr_scripts);
            } else {
                // Fallback si aucune des conditions n'est remplie
                $base_url_for_dolibarr_scripts = $request_scheme . '://' . $http_host . '/dolibarr'; // Exemple par défaut, ajuster si nécessaire
                $rcmail->write_log('plugin', "load_dolibarr_urls: Fallback - Chemin Dolibarr par défaut utilisé (sans htdocs). Base Dolibarr: " . $base_url_for_dolibarr_scripts);
            }
        }

        // Assurez-vous que l'URL de base est bien nettoyée
        $base_url_cleaned = rtrim($base_url_for_dolibarr_scripts, '/');

        // Construction des URLs finales des scripts du module Dolibarr
        $this->dolibarr_script_url = $base_url_cleaned . '/custom/mailboxmodule/scripts/save_mails.php';
        $this->dolibarr_search_target_url = $base_url_cleaned . '/custom/mailboxmodule/scripts/search_targets.php';
        $this->dolibarr_check_sender_url = $base_url_cleaned . '/custom/mailboxmodule/scripts/check_sender.php';
        $this->dolibarr_active_modules_url = $base_url_cleaned . '/custom/mailboxmodule/scripts/get_active_modules.php';

        $rcmail->write_log('plugin', "load_dolibarr_urls: Final save_mails URL: " . $this->dolibarr_script_url);
        $rcmail->write_log('plugin', "load_dolibarr_urls: Final search_targets URL: " . $this->dolibarr_search_target_url);
        $rcmail->write_log('plugin', "load_dolibarr_urls: Final check_sender URL: " . $this->dolibarr_check_sender_url);
        $rcmail->write_log('plugin', "load_dolibarr_urls: Final active_modules URL: " . $this->dolibarr_active_modules_url);
    }
    function inject_modal($args)
    {
        $modal_html = '
           <div id="save2dolibarr_modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; border:1px solid #ccc; padding:25px; max-width:90%; width:900px; max-height:90vh; overflow-y:auto; box-shadow:0 4px 20px rgba(0,0,0,0.15); border-radius:8px; z-index:1000;">
        <h3 style="margin-top:0; color:#333; padding-bottom:10px; border-bottom:1px solid #eee;">Enregistrer dans Dolibarr</h3>

        <div style="margin:15px 0; padding:15px; background:#f8f9fa; border-radius:6px;">
            <table style="width:100%; border-collapse: collapse; font-size: 14px;">
                <tbody>
                    <tr>
                        <td style="width:120px; padding:8px; vertical-align: middle; font-weight:bold;">
                            Tiers :
                        </td>
                        <td style="padding:8px;">
                            <span id="sender_info" style="font-weight:500;">Recherche en cours...</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p style="margin-bottom:15px; font-weight:bold; color:#444;">Sélectionnez un ou plusieurs modules et recherchez la cible :</p>

        <div style="margin-bottom:20px; background:#f8f9fa; padding:20px; border-radius:6px; border:1px solid #ddd;">
            <label style="font-weight: bold; display:block; margin-bottom:15px; color:#444;">Modules cibles :</label>
            
            <div id="active_modules_checkbox_container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; max-height:300px; overflow-y:auto; padding-right:10px;">
                <div style="text-align: center; padding: 20px;">Chargement des modules...</div>
            </div>
        </div>

        <div id="target_inputs_container" style="margin-top:20px;">
            </div>

        <div id="target_suggestions" style="border:1px solid #ddd; max-height:200px; overflow-y:auto; margin-top:15px; padding:10px; background:#f8f9fa; border-radius:4px; display:none;"></div>

        <div style="margin-top:25px; text-align:right; padding-top:15px; border-top:1px solid #eee;">
            <button id="save2dolibarr_submit" style="margin-right:10px; padding:10px 20px; background:#2e7d32; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Enregistrer</button>
            <button id="save2dolibarr_submit_only" style="margin-right:10px; padding:10px 20px; background:#1565c0; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Enregistrer sans lien</button>
            <button id="save2dolibarr_close" style="padding:10px 20px; background:#757575; color:white; border:none; border-radius:4px; cursor:pointer;">Annuler</button>
        </div>
    </div>

    <div id="save2dolibarr_overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;"></div>';

        $args['content'] .= $modal_html;
        $rcmail = rcmail::get_instance();
        $rcmail->output->set_env('save2dolibarr_check_sender_url', $this->dolibarr_check_sender_url);
        return $args;

    }

    function get_sender_email()
    {
        $rcmail = rcmail::get_instance();
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_GPC);

        if (!$uid) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'UID manquant']);
            exit;
        }

        $message = new rcube_message($uid);
        $from_header = $message->get_header('from');
        $sender_email = $this->extract_email($from_header);

        header('Content-Type: application/json');
        echo json_encode(['email' => $sender_email]);
        exit;
    }

    private function extract_email($from_header) {
        if (preg_match('/<([^>]+)>/', $from_header, $matches)) {
            return $matches[1];
        }
        if (filter_var($from_header, FILTER_VALIDATE_EMAIL)) {
            return $from_header;
        }
        return null;
    }


    function save_mail()
    {
        $rcmail = rcmail::get_instance();
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_GPC);
        $mbox = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_GPC);
        $targets_json = rcube_utils::get_input_value('_links', rcube_utils::INPUT_GPC);
        $targets = json_decode($targets_json, true);
        if (is_null($targets)) {
            $targets = [];
        }

        if (is_array($uid)) $uid = $uid[0];

        try {
            $storage = $rcmail->get_storage();
            $message = new rcube_message($uid);

            $raw_email_content = $storage->get_raw_body($uid);
            if (!$raw_email_content) throw new Exception("Impossible de récupérer le contenu brut de l'e-mail.");

            $from_header = $message->get_header('from');
            $subject = $message->subject;
            $raw_date = $message->get_header('date');
            $datetime_sql = null;
            if (!empty($raw_date)) {
                $timestamp = strtotime($raw_date);
                if ($timestamp !== false) $datetime_sql = date('Y-m-d H:i:s', $timestamp);
            }

            $attachments = [];
            $upload_dir = __DIR__ . '/../data/fichier_join/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0775, true);

            $parts = $message->mime_parts;
            if (is_array($parts)) {
                foreach ($parts as $part) {
                    if ($part->disposition == 'attachment' || $part->disposition == 'inline') {
                        $filename = rcube_mime::decode_mime_string($part->filename, 'UTF-8');
                        if (!$filename) $filename = 'unnamed_' . uniqid() . '.bin';
                        $filename = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $filename);
                        $file_content = $message->get_part_content($part->mime_id);
                        if ($file_content !== false) {
                            $filepath = $upload_dir . $uid . '_' . $filename;
                            file_put_contents($filepath, $file_content);
                            $attachments[] = ['name' => $filename, 'path' => $filepath];
                        }
                    }
                }
            }

            $payload = json_encode([
                'uid' => $uid,
                'mbox' => $mbox,
                'rc_user_email' => $rcmail->user->get_username(),
                'from' => $from_header,
                'subject' => $subject,
                'date' => $datetime_sql,
                'raw_email' => $raw_email_content,
                'attachments' => $attachments,
                'links' => $targets
            ]);

            $ch = curl_init($this->dolibarr_script_url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 20
            ]);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err = curl_error($ch);
            curl_close($ch);

            if ($curl_err) throw new Exception("Erreur cURL : $curl_err");
            if ($http_code !== 200) throw new Exception("Dolibarr a répondu HTTP $http_code");

            $json = json_decode($response, true);
            if (!isset($json['status']) || $json['status'] !== 'OK') {
                $msg = $json['message'] ?? 'Réponse Dolibarr non reconnue';
                throw new Exception(" Dolibarr : $msg");
            }

            $rcmail->output->command('display_message', ' ' . $json['message'], 'confirmation');

        } catch (Throwable $e) {
            $rcmail->output->command('display_message', '' . $e->getMessage(), 'error');
        }

        $rcmail->output->send('plugin');
    }

    function search_targets()
    {
        $q = rcube_utils::get_input_value('q', rcube_utils::INPUT_GPC);
        $type = rcube_utils::get_input_value('type', rcube_utils::INPUT_GPC);

        $response = file_get_contents($this->dolibarr_search_target_url . "?q=" . urlencode($q) . "&type=" . urlencode($type));

        header('Content-Type: application/json');
        echo $response;
        exit;
    }

    function get_active_dolibarr_modules()
    {
        $dolibarr_url = $this->dolibarr_active_modules_url;

        $ch = curl_init($dolibarr_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $http_code !== 200) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch active modules from Dolibarr.']);
            exit;
        }

        header('Content-Type: application/json');
        echo $response;
        exit;
    }
}
?>