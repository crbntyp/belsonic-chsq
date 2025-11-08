#!/bin/bash

# Shine Festivals - Docker Development Script
# This script manages the Docker container with volume mounting for development

PROJECT_DIR="/Users/carbontype/Documents/Engineering/crbntyp/shine festivals"
CONTAINER_NAME="shine-festivals-web"
IMAGE_NAME="shine-festivals"

case "$1" in
  start)
    echo "Starting Docker container with volume mount..."
    docker run -d \
      --name $CONTAINER_NAME \
      -p 8080:80 \
      --add-host=host.docker.internal:host-gateway \
      -v "$PROJECT_DIR/dist":/var/www/html \
      $IMAGE_NAME

    # Fix permissions for uploads
    docker exec $CONTAINER_NAME chown -R www-data:www-data /var/www/html/img

    echo "Container started at http://localhost:8080"
    ;;

  stop)
    echo "Stopping Docker container..."
    docker stop $CONTAINER_NAME
    docker rm $CONTAINER_NAME
    echo "Container stopped"
    ;;

  restart)
    echo "Restarting Docker container..."
    $0 stop
    $0 start
    ;;

  rebuild)
    echo "Rebuilding Docker image..."
    $0 stop
    docker build -t $IMAGE_NAME "$PROJECT_DIR" -q
    $0 start
    ;;

  logs)
    docker logs -f $CONTAINER_NAME
    ;;

  *)
    echo "Usage: $0 {start|stop|restart|rebuild|logs}"
    echo ""
    echo "  start   - Start the Docker container with volume mount"
    echo "  stop    - Stop and remove the container"
    echo "  restart - Restart the container"
    echo "  rebuild - Rebuild the image and restart the container"
    echo "  logs    - Show container logs (follow mode)"
    exit 1
    ;;
esac
