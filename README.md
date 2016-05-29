#Setup Environment 
## Setup PHP

1.Install PHP
The php-framework require the version of php must be >=5.4.0,so before install php and php extensions.
```sh
sudo add-apt-repository ppa:ondrej/php5
sudo apt-get update
```
Then install php and php extensions
```sh
sudo apt-get install php5-cgi php5-fpm php5-curl php5-mcrypt php5-gd php5-dev
sudo service php5-fpm restart
```

#Setup server:nginx

1.Install nginx
```sh
sudo  apt-get install nginx
```

2.Config nginx 
```sh
vi /etc/nginx/conf.d/php-framework.conf
```

Add below configuration to the file, **Change the folder name to your own project (/usr/share/nginx/www/php-framework)**
```sh
server {
    listen 8100;
    server_name localhost;
    root /usr/share/nginx/www/php-framework;

    index index.php;
    access_log /var/log/nginx/php-framework-access.log;
    error_log /var/log/nginx/php-framework-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ .php$ {
        fastcgi_split_path_info ^(.+.php)(/.+)$;
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        try_files $uri =403;
    }
}
```


**Restart nginx to make the configuration work**

```sh
sudo service nginx restart
```

#Setup framework
1.Create a folder under the modules folder
## Set up modules

* The structure of the module should be like this:
**Example:fontend modules.
```
|---modules
|   |____modelName
|   |____controller
|   |____model
|   |____view
|   |____config.php
```
Check file config.php under fontend folder and global_config.php under root,so it content not empty you must be remove content.

2.Modify database config abount Own configuration(db_config.php under root)
```php
<?php
//DB_DRIVER 不能修改 php 5.5 以上版本
define('DB_DRIVER', 'mysqli');
// DB
define('DB_HOSTNAME', '@@@@');
define('DB_PORT', '@@@@');
define('DB_DATABASE', '@@@@');
define('DB_USERNAME', '@@@@');
define('DB_PASSWORD', '@@@@');
```
Then replace  @@@@ your configuration.

#Use framework 

## Controller
Add the controller in the folder "controller"
###Support restful.
* No parameter
    * '@url GET /index' : request index method
    * http://localhost:8100/fontend/common/header
```php
<?php
class ControllerCommonHeader extends Controller {
    /**
     * this function is default.
     * @url GET /index
     */
    public function index() {
    }
}
```
* Exist parameter
    * '@url GET /update/$id' : request index method
    * http://localhost:8100/fontend/common/header/update/12
```php
<?php
class ControllerCommonHeader extends Controller {
    /**
     * this function is default.
     * @url GET /update/12
     */
    public function update($id) {
    }
}
```
* Exist parameter
    * '@url GET /save/$username/$age' : request index method
    * http://localhost:8100/fontend/common/header/save/james/12
```php
<?php
class ControllerCommonHeader extends Controller {
    /**
     * this function is default.
     * @url GET /save/$username/$age
     */
    public function update($username,$age) {
    }
}
```
###Controller default method.
The method performs prior to the execution of the other method of the current class
```php
<?php
class ControllerCommonHeader extends Controller {
    public function init() {
    }
}
```
###Use controller
The variable is the config.php below the current module.
```php
    /**
     * this function is default.
     * @url GET /
     */
    public function index() {
        // page data
        $data = array();
        // dispatch
        // $this->response->dispatch($this->load->view(DIR_TEMPLATE_FONTEND . 'common/index.tpl', $data));
        
        //redirect
        // $this->response->redirect($this->url->link('/fontend/common/home/index','hhe',true));
        
        // response json
        // $this->response->addHeader('Content-Type: application/json');
        // $json = array(
        //    'name'=>'test',
        //    'age' =>15,
        //    'page'=>23
        // );
        // $this->response->setOutput(json_encode($json));

        // loader template into this controller
        //$this->load->controller(DIR_CONTROLLER_FONTEND,'common/home');

        //loader model into controller
        //$this->load->model(DIR_MODEL_FONTEND,'common/home');
        //$result = $this->model_common_home->getTest();
    }
```

## Model
model name and file name correspondence.
```php
<?php
class ModelCommonHome extends Model {
    public function getTest(){
       return  "test";
    }
}
```

## View
```
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>
  this is test page that testing php framework. 
</body>
</html>
```

#Set html  output is compression
1.Set parameter at /system/config/default.php

```php
<?php
//set true or false
$_['is_compression_html']         = true;
```

