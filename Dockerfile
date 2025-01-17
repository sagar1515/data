# Use the latest Nginx image
FROM nginx:latest

# Copy Nginx configuration
COPY ./nginx.conf /etc/nginx/nginx.conf

# Copy SSL certificates
COPY ./nginx/ssl /etc/nginx/ssl

# Expose ports for HTTP and HTTPS
EXPOSE 80 443

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]


