FROM node:latest
RUN mkdir /app
WORKDIR /app
COPY . /app
RUN npm install
RUN rm /bin/sleep
RUN mv /app/flag /
RUN chmod 555 /app
EXPOSE 1337
RUN useradd -u 1337 webapp
USER webapp

CMD ["node", "server.js"]