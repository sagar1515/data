pipeline {
    agent any

    environment {
        DOCKER_IMAGE = 'handyman_service_web_image'
        DOCKER_TAG = 'latest'
        SSL_CERT_DIR = '/etc/letsencrypt/live/pipeline.iqonic.design' // Define SSL certificate location
        DOMAIN = 'pipeline.iqonic.design' // Your domain name
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    docker.build("${DOCKER_IMAGE}:${DOCKER_TAG}")
                }
            }
        }

        stage('Copy SSL Certificates') {
            steps {
                script {
                    // Ensure SSL certificates are available to the Docker container
                    sh '''
                        mkdir -p ./nginx/ssl
                        cp ${SSL_CERT_DIR}/fullchain.pem ./nginx/ssl/fullchain.pem
                        cp ${SSL_CERT_DIR}/privkey.pem ./nginx/ssl/privkey.pem
                    '''
                }
            }
        }

        stage('Run Docker Containers') {
            steps {
                script {
                    // Run docker-compose to start containers (app, nginx, db)
                    sh 'docker-compose -f docker-compose.yml up -d'
                }
            }
        }

        stage('Deploy to Server') {
            steps {
                script {
                    // Add deployment steps here, such as copying files to a remote server
                    echo 'Deployment steps go here'
                }
            }
        }

        stage('Clean Up') {
            steps {
                script {
                    // Shut down the containers after deployment (optional)
                    sh 'docker-compose down'
                }
            }
        }
    }

    post {
        always {
            cleanWs()
        }
    }
}

