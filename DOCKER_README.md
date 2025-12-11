This repository includes a production-ready Dockerfile suitable for Render.com.

Quick start (local build):

```bash
# Build the image (example tag)
docker build -t ksa-site:latest .

# Run locally exposing port 8080
docker run --rm -p 8080:8080 ksa-site:latest

# Visit http://localhost:8080
```

Notes for Render:
- Render provides a `PORT` env variable. The `Dockerfile` adjusts Apache to listen on the provided port (default 8080).
- Move secrets out of `.env` into Render's environment variables in the Render dashboard.
- If you need composer dependencies, install composer in the Dockerfile and run `composer install` before copying files, or use a multi-stage build.

Linting / validation:
- You can validate the Dockerfile with `hadolint` and build locally with `docker build` as shown above.
