#!/bin/bash
echo "sudo /opt/lampp/lampp start"
gnome-terminal --command="sudo /opt/lampp/lampp start"

echo "php artisan serve"
gnome-terminal --command="php artisan serve"

echo "php artisan schedule:work"
gnome-terminal --command="php artisan schedule:work"

echo "php artisan queue:work"
gnome-terminal --command="php artisan queue:work"







