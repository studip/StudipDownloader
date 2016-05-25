<?php

require_once 'app/controllers/plugin_controller.php';

class DownloadareaController extends PluginController {

    public function overview_action()
    {
        Navigation::activateItem("/downloader");
        $this->releases = $this->plugin->get_svn_list('/tags');
        $this->releases = array_reverse($this->releases->getArrayCopy());
    }
}