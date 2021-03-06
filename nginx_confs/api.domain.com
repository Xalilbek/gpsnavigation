server {
    client_max_body_size 40m;
    large_client_header_buffers 4 100k;

	root /home/gpsuser/sites/api.domain/public;
	index index.html index.htm index.php;
	server_name api.utigps.com;

	location / {
		#try_files $uri $uri/ /index.php?$query_string;
		try_files $uri $uri/ /index.php?_url=$uri&$args;
	}

	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php7.2-fpm.sock;
	}

	location ~* ^.+\.(jpg|jpeg|gif|png|ico|css|pdf|ppt|txt|bmp|rtf|js)$ {
        access_log off;
    	expires 3d;
    }
}
