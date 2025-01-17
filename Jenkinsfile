pipeline {
    agent any

    environment {
        DOCKER_IMAGE = 'handyman_service_web_image'
        DOCKER_TAG = 'latest'
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
                    // Build the Docker image using the Dockerfile
                    docker.build("${DOCKER_IMAGE}:${DOCKER_TAG}")
                }
            }
        }

        stage('Run Docker Containers') {
            steps {
                script {
                    // Use the sh step to run docker-compose
                    sh 'docker-compose -f docker-compose.yml up -d'
                }
            }
        }

        stage('Deploy to Server') {
            steps {
                script {
                    // Add deployment steps here (e.g., copy to production server)
                    // You can use SCP, rsync, or other tools to deploy the image to a server if required
                }
            }
        }

        stage('Clean Up') {
            steps {
                script {
                    // Use the sh step to stop and remove the containers
                    sh 'docker-compose down'
                }
            }
        }
    }

    post {
        always {
            cleanWs() // Clean workspace after the build
        }
    }
}

