FROM nginx:alpine

# Если нам нужно чтобы скрипт работал на 80 порту, то надо удалить дефолтный конфиг nginx
# RUN rm /etc/nginx/conf.d/default.conf

COPY ./hosts/local.conf /etc/nginx/conf.d/local.conf

EXPOSE 8080

CMD ["nginx", "-g", "daemon off;"]