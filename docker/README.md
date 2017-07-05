# HubDrop on Docker

We've converted HubDrop to use Docker. 

Copy the `docker-compose.yml` file to your docker host and run `docker-compose up -d; docker-compose logs -f` to launch a hubdrop, editing the file as needed.
 
Once you run `docker-compose up`, you will get a clone of the hubdrop app source code in `./hubdrop_home/app`, and you will see Jenkins home directory files in `./jenkins_home`. 
 
 You can visit the hubdrop front-end at http://localhost and the Jenkins server at http://localhost:8080.
 
 
 ## Configuring HubDrop
 
You will need to edit `parameters.yml` for hubdrop to work. 

Edit the file directly: `./hubdrop_home/app/app/config/parameters.yml`.

