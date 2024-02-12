#!/bin/bash

gnome-terminal --tab --title="Lampp" -- clear sudo /opt/lampp/lampp start

gnome-terminal --tab --title="Artisan Serve" -- clear php artisan serve

gnome-terminal --tab --title="Schedule Work" -- clear cd llservicios/ && php artisan schedule:work

gnome-terminal --tab --title="Queue Work" -- clear cd llservicios/ && php artisan queue:work
