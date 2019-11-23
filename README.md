PhpPoem
=================
>PhpPoem, 如诗一般简洁优美的PHP框架       
>PhpPoem, a simple and beautiful php framework, php will be like poet.

Home: [http://phppoem.com](http://phppoem.com)  
Author: [Cleey](http://www.cleey.com)  
QQ Group: 137951449  

快速安装(类unix)
-----------------
>站点根目录shell输入`git clone https://github.com/cleey/phppoem`部署框架代码  
>配置Hosts如下,并添加本地测试域
~~~
echo "127.0.0.1 dev.phppoem.com" >> /etc/hosts
~~~
>配置Nginx rewrite及path,如下
~~~
server {
    listen       80; 
    server_name  dev.phppoem.com;
    index index.php index.html index.shtml;

    #默认路径指向phppoem项目的public目录下
    root  /path/www/phppoem/public;  

    #phppoem Url Rewrite
    location /{
        if (!-e $request_filename) {
            rewrite  ^/(.*)$  /index.php/$1  last;
            break;
        }   
    }   

    #phppoem Path Info
    location ~ \.php($|/) {
        fastcgi_pass  127.0.0.1:9000;
        fastcgi_index index.php; 
        fastcgi_split_path_info  ^(.+\.php)(/.*)$;  
        fastcgi_param  PATH_INFO $fastcgi_path_info;
        fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
        include fastcgi.conf;
    }
}
~~~
>重载Nginx, 输入以下命令使配置文件生效
~~~
nginx -s reload
~~~
>浏览器输入`http://dev.phppoem.com`, 框架会自动为您在目部录下构建测试项目`/app`，并在浏览器为您呈现Success!  
>现在！ 开始感受PhpPoem诗般的优雅吧！


压力测试   
-----------------
服务器使用配置为 CPU：16核， RAM：16G测试 ，php5.3.3开启opcache，使用压测工具ab，结果如下： 
~~~
ab -c7500 -t10 test.com   
Requests per second:    7836.84 [#/sec] (mean)   
Time per request:       957.019 [ms] (mean)   
Time per request:       0.128 [ms] (mean, across all concurrent requests)   
Transfer rate:          1642.15 [Kbytes/sec] received  
~~~
PhpPoem 2.0 并发 7500 持续10s，结果  7836.84 req/s
