pipeline {
    agent any

    environment {
        DOCKER_IMAGE = 'handyman_service_web_image'
        DOCKER_TAG = 'latest'
        DOCKER_COMPOSE = '/usr/local/bin/docker-compose'  // Path to your docker-compose
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

        stage('Run Docker Containers') {
            steps {
                script {
                    // Use Docker Compose to bring up containers (app, db, and nginx)
                    sh """
                        ${DOCKER_COMPOSE} -f docker-compose.yml up -d
                    """
                }
            }
        }

        stage('Deploy to Server') {
            steps {
                script {
                    // Here you can add commands to deploy your app to production server if needed
                    echo 'Deployment steps can be added here.'
                }
            }
        }

        stage('Clean Up') {
            steps {
                script {
                    // Bring down containers after deployment
                    sh """
                        ${DOCKER_COMPOSE} down
                    """
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


