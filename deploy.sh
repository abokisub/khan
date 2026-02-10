#!/bin/bash

# AmtPay Deployment Script
# Usage: ./deploy.sh "Your commit message"

if [ -z "$1" ]; then
    echo "Error: No commit message provided."
    echo "Usage: ./deploy.sh \"Your commit message\""
    exit 1
fi

echo "========================================"
echo "🚀 Starting Deployment Process"
echo "========================================"

# 1. Clean previous build artifacts to prevent stale file issues
echo "🧹 Cleaning up old build artifacts..."
rm -rf frontend/build
rm -rf public/static
rm public/asset-manifest.json 2>/dev/null

# 2. Build React Frontend
echo "🏗️  Building Frontend..."
cd frontend
npm run build
if [ $? -ne 0 ]; then
    echo "❌ Build failed! Aborting."
    exit 1
fi
cd ..

# 3. Verified sync of build files to public
echo "📂 Syncing build files to public directory..."
cp -r frontend/build/* public/

# 4. Stage all changes (including deletions of old hashed files)
echo "📦 Staging changes..."
git add -A

# 5. Commit and Push
echo "💾 Committing and Pushing..."
git commit -m "$1"
git push khan main

echo "========================================"
echo "✅ Deployment Complete!"
echo "Now run this on your server:"
echo "git pull khan main && php artisan optimize:clear"
echo "========================================"
