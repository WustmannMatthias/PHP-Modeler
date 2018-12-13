# PHP Modeler
This project allows to model the architecture of any PHP application into a Graph database. It supposes you annotate your php files with their corresponding features in your applications.

Once modelled, PHP Modeler will allow you to have a better understanding of the topology of your projects, will give you some useful informations and statistics, like useless namespaces or broken dependencies, but will mainly help you to analyse the impact of a modification in your application, by telling you exactly which features you have to test before publishing a new version.


#### Installation (Ubuntu) :

Clone the repository into your (apache) server directory : 
```console
$ cd /var/www/html
$ sudo git clone https://github.com/WustmannMatthias/PHP-Modeller
```

Install dependencies : 
```
$ cd PHP-Modeller
$ composer install
```

Fill informations about your neo4j database into the /data/general_settings/database file of the project, and then make your server owner of the /data directory of the project : 
```
$ sudo chown -R www-data:www-data data
```

#### How to use :

To model an application, just clone her repository into the /data/projects directory of this application : 
```
$ cd /var/www/html/PHP-Modeller/data/projects
$ sudo git clone <your_project_url>
```

Go to the user interface of PHP-Modeller simply by typing following URL in the URL field of your browser, 
```
localhost/PHP-Modeller
```
and follow the instructions !

