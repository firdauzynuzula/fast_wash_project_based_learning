pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Syntax Check (Lint)') {
            steps {
                echo "Memeriksa sintaks seluruh file PHP..."
                sh '''
                    find . -type f -name "*.php" -not -path "./vendor/*" -exec php -l {} \\;
                '''
            }
        }

        stage('Deploy to Staging') {
            when {
                branch 'staging'
            }
            steps {
                echo "Branch staging terdeteksi! Melakukan deployment ke environment Staging..."
                sh '''
                    docker compose -f docker-compose.staging.yml -p fastwash-staging down
                    docker compose -f docker-compose.staging.yml -p fastwash-staging up -d --build
                '''
            }
        }

        stage('Deploy to Production') {
            when {
                branch 'production'
            }
            steps {
                // Menunggu persetujuan / konfirmasi manual dari user di UI Jenkins
                input message: 'Apakah Anda yakin ingin mendeploy ke server PRODUCTION (fastwash.my.id)?', ok: 'Ya, Deploy ke Production'

                echo "Approval diterima! Melakukan deployment ke environment Production..."
                sh '''
                    docker compose -f docker-compose.prod.yml -p fastwash-prod down
                    docker compose -f docker-compose.prod.yml -p fastwash-prod up -d --build
                '''
            }
        }
    }

    post {
        success {
            script {
                if (env.BRANCH_NAME == 'staging') {
                    echo "SUCCESS: Staging berhasil di-deploy! Akses: http://staging.fastwash.my.id"
                } else if (env.BRANCH_NAME == 'production') {
                    echo "SUCCESS: Production berhasil di-deploy! Akses: http://fastwash.my.id"
                } else {
                    echo "SUCCESS: Build & test berhasil untuk branch ${env.BRANCH_NAME} (tidak ada deployment)."
                }
            }
        }
        failure {
            echo "FAILED: Terjadi kesalahan saat build atau deployment. Cek console log Jenkins!"
        }
    }
}
