<?php

class StudipDownloader extends StudipPlugin implements SystemPlugin
{

    //private $svnurl = 'svn://develop.studip.de/studip';
    //private $svncmd = 'svn --no-auth-cache --username studip --password studip ';

    private $svnurl = 'file:///home/svn/studip';
    private $svncmd = '/usr/bin/svn';

    function perform($unconsumed_path) {
        try {
            parent::perform($unconsumed_path);
        } catch (Exception $exception) {
            if ($exception instanceOf Trails_Exception) {
                $status = $exception->getCode();
            } else {
                $status = 500;
            }
            header('HTTP/1.1 ' . $status . ' ' . $exception->getMessage());
            $this->render_json(array('status' => (int)$status, 'message' => $exception->getMessage()));
        }
    }

    function releases_action()
    {
        $releases = $this->get_svn_list('/tags');
        if ($releases) {
            $this->render_json(array_values($releases->orderBy('name desc')->toArray()));
        } else {
            throw new Trails_Exception(500);
        }
    }

    function branches_action()
    {
        $releases = $this->get_svn_list('/branches');
        if ($releases) {
            $this->render_json(array_values($releases->orderBy('name desc')->toArray()));
        } else {
            throw new Trails_Exception(500);
        }
    }

    function download_action($what = 'stable')
    {
        if ($what == 'stable') {
            $releases = $this->get_svn_list('/tags');
            $releases->orderBy('name desc');
            $download = '/tags/' . $releases->val('name');
            $filename = 'studip-release-' . $releases->val('name');
        } elseif ($what == 'latest-stable') {
            $releases = $this->get_svn_list('/tags');
            $releases->orderBy('name desc');
            $download = '/branches/' . $releases->val('name');
            $branches = $this->get_svn_list('/branches');
            $filename = 'studip-' . $releases->val('name') . '-r' . $branches->findOneBy('name', $releases->val('name'))->rev;
        } elseif ($what == 'beta') {
            $branches = $this->get_svn_list('/branches');
            $branches->orderBy('name desc');
            $download = '/branches/' . $branches->val('name');
            $filename = 'studip-' . $branches->val('name') . '-r' . $branches->val('rev');
        } elseif (strpos($what, 'stable') === 0) {
            list(,$release) = explode('-', $what);
            $releases = $this->get_svn_list('/tags');
            $stable = $releases->findBy('name', $release, '^=')->orderBy('name desc')->val('name');
            if ($stable) {
                $download = '/tags/' . $stable;
                $filename = 'studip-release-' . $stable;
            }
        } elseif (strpos($what, 'latest') === 0) {
            list(,$branch) = explode('-', $what);
            $branches = $this->get_svn_list('/branches');
            if ($branches->findOneBy('name', $branch)) {
                $download = '/branches/' . $branch;
                $filename = 'studip-' . $branch . '-r' . $branches->findOneBy('name', $branch)->rev;
            }
        } else {
            $releases = $this->get_svn_list('/tags');
            if ($releases->findOneBy('name', $what)) {
                $download = '/tags/' . $what;
                $filename = 'studip-release-' . $what;
            }
        }
        if ($download) {
            $id = $this->prepare_download($download, $filename . '.zip');
            header("Expires: Mon, 12 Dec 2001 08:00:00 GMT");
            header("Last-Modified: " . gmdate ("D, d M Y H:i:s") . " GMT");
            header("Pragma: public");
            header("Cache-Control: private");
            header("Content-Type: application/zip");
            header("Content-Length: " . filesize($GLOBALS['TMP_PATH'] . '/' . $id . '.zip'));
            header("Content-Disposition: attachment; filename=\"$filename.zip\"");
            readfile($GLOBALS['TMP_PATH'] . '/' . $id . '.zip');
        } else {
            throw new Trails_Exception(400);
        }
    }

    function prepare_download($path, $filename)
    {
        $id = md5($filename);
        if (!file_exists($GLOBALS['TMP_PATH'] . '/' . $id . '.zip')) {
            @rmdirr($GLOBALS['TMP_PATH'] . '/' . $id);
            exec($this->svncmd . ' --force export ' . $this->svnurl . $path . ' ' . $GLOBALS['TMP_PATH'] . '/' . $id, $out, $ret);
            create_zip_from_directory($GLOBALS['TMP_PATH'] . '/' . $id, $GLOBALS['TMP_PATH'] . '/' . $id . '.zip');
            @rmdirr($GLOBALS['TMP_PATH'] . '/' . $id);
            return $id;
        } else {
            return $id;
        }
    }

    function get_svn_list($path)
    {
        $out = array();
        exec($this->svncmd . ' list --xml ' . $this->svnurl . $path, $out, $ret);
        $list = new SimpleCollection();
        if ($ret === 0) {
            $taglist = simplexml_load_string(join("", $out));
            foreach ($taglist->xpath('//entry') as $entry) {
                $name = (string)$entry->name;
                $date = (string)$entry->commit->date;
                $rev = (string)$entry->commit['revision'];
                $list[] = compact('name', 'date', 'rev');
            }
            return $list;
        }
    }


    function render_json($data)
    {
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(studip_utf8encode($data));
    }
}
