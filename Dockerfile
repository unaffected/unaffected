FROM node:19-slim

WORKDIR /app

COPY **/package.json **/package.json
COPY **/tsconfig*.json **/tsconfig*.json
COPY **/vitest*.ts **/vitest*.ts

RUN npm i -g pnpm@8.2.0

RUN pnpm install

COPY . .

RUN pnpm build
