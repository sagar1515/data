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
                    docker.build("${DOCKER_IMAGE}:${DOCKER_TAG}")
                }
            }
        }

        stage('Run Docker Containers') {
            steps {
                script {
                    docker-compose -f docker-compose.yml up -d
                }
            }
        }

        stage('Deploy to Server') {
            steps {
                script {
                    // Add deployment steps here (e.g., copy to production server)
                }
            }
        }

        stage('Clean Up') {
            steps {
                script {
                    docker-compose down
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

