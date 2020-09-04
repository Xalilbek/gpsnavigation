server {
    client_max_body_size 40m;
    large_client_header_buffers 4 100k;

	root /home/gpsuser/sites/site.domain.com/public;
	index index.html index.htm index.php;
	server_name utigps.com www.utigps.com;

	location / {
        try_files $uri $uri/ /index.html;
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
