<?php

namespace FAL;

class Dropbox implements FileSystem {

    protected
        $appKey,
        $appSecret,
        $authToken,
        $authSecret,
        $authParams;
    /** @var \Base */
    protected $f3;

    /** @var \Web */
    protected $web;

    const
        E_APIERROR = 'Dropbox API Error: %s';

    public function __construct($appKey,$appSecret) {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->f3 = \Base::instance();
        $this->web = \Web::instance();
        $this->web->engine('curl');
        $this->authToken = $this->f3->get('SESSION.dropbox.authToken');
        $this->authSecret = $this->f3->get('SESSION.dropbox.authSecret');
        $this->authParams = array(
            'oauth_consumer_key' => $this->appKey,
            'oauth_token' => $this->authToken,
            'oauth_version' => '1.0',
            'oauth_signature' => $this->appSecret.'&'.$this->authSecret,
            'oauth_signature_method' => 'PLAINTEXT',
         // 'oauth_timestamp' => strftime("%a, %d %b %Y %H:%M:%S %Z",time()),
        );
    }

    /**
     * perform external authorisation
     */
    public function login() {
        $this->requestToken();
        $this->authorize('http://localhost/web/git/fatfree_ikkez/dropbox-login-complete');
    }

    /**
     * AUTH Step 1: request a token for authorisation process
     */
    public function requestToken(){
        $url = 'https://api.dropbox.com/1/oauth/request_token';
        $result = $this->doOAuthCall($url, 'POST');
        parse_str($result['body'], $output);
        if (array_key_exists('oauth_token_secret',$output) &&
            array_key_exists('oauth_token', $output))
        {
            $this->authToken = $output['oauth_token'];
            $this->authSecret = $output['oauth_token_secret'];
            $this->f3->set('SESSION.dropbox.authToken',$this->authToken);
            $this->f3->set('SESSION.dropbox.authSecret',$this->authSecret);
        } else {
            $result = json_decode($result['body'], true);
            trigger_error('OAuth failed: '.$result['error']);
        }
    }

    /**
     * AUTH Step 2: reroute to auth page
     * @param null $callback_url
     */
    public function authorize($callback_url = NULL){
        $url = 'https://www.dropbox.com/1/oauth/authorize';
        $params = array(
            'oauth_token' => $this->f3->get('SESSION.dropbox.authToken'),
            'locale ' => $this->f3->get('LANGUAGE'),
        );
        if($callback_url) $params['oauth_callback'] = $callback_url;
        $this->f3->reroute($url.'?'.http_build_query($params));
    }

    /**
     * AUTH Step 3: request access token, used to sign all resource requests
     * @return bool
     */
    public function accessToken(){
        $url = 'https://api.dropbox.com/1/oauth/access_token';
        $result = $this->doOAuthCall($url,'POST');
        parse_str($result['body'], $output);
        if (!count(array_diff(array('oauth_token','oauth_token_secret','uid'),
            array_keys($output))))
        {
            $this->authToken = $output['oauth_token'];
            $this->authSecret = $output['oauth_token_secret'];
            $this->uid = $output['uid'];
            $this->f3->set('SESSION.dropbox.authToken', $this->authToken);
            $this->f3->set('SESSION.dropbox.authSecret', $this->authSecret);
            return true;
        } else {
            $result = json_decode($result['body'], true);
            trigger_error('OAuth failed: '.$result['error']);
            return false;
        }
    }

    /**
     * perform a signed oauth request
     * @param       $url
     * @param       $method
     * @param array $params
     * @param null  $type
     * @param null  $file
     * @param null  $content
     * @return bool
     */
    protected function doOAuthCall($url, $method, $params=null,
                                   $type=NULL, $file=NULL, $content=NULL) {
        if(is_null($params)) $params = array();
        $method = strtoupper($method);
        $options = array('method' => $method);
        if ($method == 'GET') {
            if($file)
                $url .= $type.'/'.$file;
            $url .= '?'.http_build_query($this->authParams + $params);
        }
        elseif ($method == 'POST') {
            $params = $this->authParams + $params + array('root' => $type);
            $options['content'] = http_build_query($params);
        }
        elseif ($method == 'PUT') {
            $url .= $type.'/'.$file.'?'.http_build_query($this->authParams + $params);
            $options['content'] = $content;
            $options['header'] = array('Content-Type: application/octet-stream');
        }
        else {
            trigger_error('unknown METHOD');
            return false;
        }
        return $this->web->request($url, $options);
    }

    /**
     * gather user account information
     * @return bool|mixed
     */
    public function getAccountInfo() {
        $url = 'https://api.dropbox.com/1/account/info';
        $result = $this->doOAuthCall($url,'POST');
        $result_body = json_decode($result['body'], true);
        if (!array_key_exists('error', $result_body)) {
            return $result_body;
        } else {
            trigger_error(sprintf(self::E_APIERROR,$result_body['error']));
            return false;
        }
    }

    /**
     * return file content
     * @param        $file
     * @param null   $rev
     * @param string $type
     * @return mixed
     */
    public function read($file, $rev=NUll, $type='sandbox')
    {
        $url = 'https://api-content.dropbox.com/1/files/';
        $params = array();
        if ($rev) $params['rev'] = $rev;
        $result = $this->doOAuthCall($url,'GET',$params, $type, $file);
        // if file not found, response is json, otherwise just file contents
        if(!in_array('HTTP/1.1 404 Not Found', $result['headers']))
            return $result['body'];
        else {
            $result_body = json_decode($result['body'], true);
            trigger_error(sprintf(self::E_APIERROR, $result_body['error']));
            return false;
        }
    }

    /**
     * determine if the file exists
     * @param        $file
     * @param bool   $hidden
     * @param null   $rev
     * @param string $type
     * @return mixed
     */
    public function exists($file, $hidden = false, $rev = NULL, $type = 'sandbox')
    {
        return $this->metadata($file, false, true, $hidden, $rev, $type);
    }

    /**
     * list directory contents
     * @param string $file
     * @param bool   $hidden
     * @param null   $rev
     * @param string $type
     * @return bool|mixed
     */
    public function listDir($file='', $hidden=false, $rev = NUll, $type = 'sandbox')
    {
        return $this->metadata($file, true, false, $hidden, $rev, $type);
    }

    /**
     * get file information
     * @param        $file
     * @param null   $rev
     * @param string $type
     * @return bool|mixed
     */
    public function fileInfo($file,$rev = NUll, $type = 'sandbox') {
        return $this->metadata($file,false,false,true,$rev,$type);
    }

    /**
     * perform meta request
     * @param        $file
     * @param bool   $list
     * @param bool   $existCheck
     * @param bool   $hidden
     * @param null   $rev
     * @param string $type
     * @return bool|mixed
     */
    protected function metadata($file,$list=true,$existCheck=false,
                                $hidden=false,$rev=NULL, $type='sandbox')
    {
        $url = 'https://api.dropbox.com/1/metadata/';
        $params = array();
        $params['list'] = $list;
        if ($rev) $params['rev'] = $rev;
        if ($list) $params['include_deleted'] = 'false';
        $result = $this->doOAuthCall($url, 'GET', $params, $type,$file);
        $result_body = json_decode($result['body'], true);
        if (!array_key_exists('error', $result_body)) {
            if($existCheck) {
                if(array_key_exists('is_deleted',$result_body) && $result_body['is_deleted'])
                    return ($hidden) ? true : false;
                else return true;
            }
            else return $result_body;
        } else {
            if($existCheck) return false;
            trigger_error(sprintf(self::E_APIERROR, $result_body['error']));
            return false;
        }
    }

    /**
     * write file content
     * @param        $file      file path
     * @param        $content   file content
     * @param string $type      sandbox or dropbox
     * @return mixed
     */
    public function write($file, $content, $type='sandbox')
    {
        $url = 'https://api-content.dropbox.com/1/files_put/';
        $result = $this->doOAuthCall($url,'PUT',null,$type,$file,$content);
        $result_body = json_decode($result['body'],true);
        if (!array_key_exists('error', $result_body)) {
            return $result_body;
        } else {
            trigger_error(sprintf(self::E_APIERROR, $result_body['error']));
            return false;
        }
    }

    /**
     * delete a file or dir
     * @param        $file
     * @param string $type
     * @return mixed
     */
    public function delete($file,$type='sandbox')
    {
        $url = 'https://api.dropbox.com/1/fileops/delete';
        $result = $this->doOAuthCall($url,'POST',array('path' => $file),$type);
        $result_body = json_decode($result['body'], true);
        if (!array_key_exists('error', $result_body)) {
            return $result_body;
        } else {
            trigger_error(sprintf(self::E_APIERROR, $result_body['error']));
            return false;
        }
    }

    /**
     * rename a file or directory
     * @param        $from
     * @param        $to
     * @param string $type
     * @return mixed
     */
    public function move($from, $to, $type='sandbox')
    {
        $url = 'https://api.dropbox.com/1/fileops/move';
        $params = array('from_path' => $from,'to_path'=>$to);
        $result = $this->doOAuthCall($url, 'POST', $params, $type);
        $result_body = json_decode($result['body'], true);
        if (!array_key_exists('error', $result_body)) {
            return $result_body;
        } else {
            trigger_error(sprintf(self::E_APIERROR, $result_body['error']));
            return false;
        }
    }

    /**
     * get last modified date
     * @param        $file
     * @param null   $rev
     * @param string $type
     * @return mixed
     */
    public function modified($file, $rev = NULL, $type = 'sandbox')
    {
        $result = $this->metadata($file, false, false, true, $rev, $type);
        return strtotime($result['modified']);
    }

    /**
     * get filesize in bytes
     * @param        $file
     * @param null   $rev
     * @param string $type
     * @return mixed
     */
    public function size($file, $rev = NULL, $type = 'sandbox')
    {
        $result = $this->metadata($file, false, false, true, $rev, $type);
        return strtotime($result['bytes']);
    }

    /**
     * return whether the item is a directory
     * @param        $dir
     * @param null   $rev
     * @param string $type
     * @return mixed
     */
    public function isDir($dir, $rev = NULL, $type = 'sandbox')
    {
        $result = $this->metadata($dir, false, true, false, $rev, $type);
        return (bool)$result;
    }

    /**
     * create new directory
     * @param        $dir
     * @param string $type
     * @return mixed
     */
    public function createDir($dir,$type='sandbox')
    {
        $url = 'https://api.dropbox.com/1/fileops/create_folder';
        $result = $this->doOAuthCall($url, 'POST', array('path'=>$dir), $type);
        $result_body = json_decode($result['body'], true);
        if (!array_key_exists('error', $result_body)) {
            return $result_body;
        } else {
            trigger_error(sprintf(self::E_APIERROR, $result_body['error']));
            return false;
        }
    }

    /**
     * remove a directory
     * @param        $dir
     * @param string $type
     * @return mixed
     */
    public function removeDir($dir,$type='sandbox')
    {
        $this->delete($dir,$type);
    }
}