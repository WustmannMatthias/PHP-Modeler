# PHP Modeler
This project allows to model the architecture of any PHP application into a Graph database. It supposes you annotate your php files with their corresponding features in your applications.
Once modelled, PHP Modeler will allow you to have a better understanding of the topology of your projects, will give you some useful informations and statistics, like useless namespaces or broken dependencies, but will mainly help you to analyse the impact of a modification in your application, by telling you exactly which features you have to test before publishing a new version.


#### Installation (Ubuntu) :

Clone the repository into your (apache) server directory : 
```console
$ cd /var/www/html
$ sudo git clone https://github.com/WustmannMatthias/PHP-Modeler
```

Make your server owner of the /data directory of the project : 
```
$ cd PHP-Modeler 
$ sudo chown -R www-data:www-data data
```

#### How to use :

To model an application, just clone her repository into the /data/projects directory of this application : 
```
$ cd /var/www/html/PHP-Modeler/data/projects
$ sudo git clone <your_project_url>
```

Go to the user interface of PHP-Modeler simply by typing following URL in the URL field of your browser, 
```
localhost/PHP-Modeler
```
and follow the instructions !
