# tier3-app

This repository contains an AWS CloudFormation template and helper scripts to deploy a highly-available 3-tier web application (VPC, RDS MySQL, EC2 Auto Scaling, Application Load Balancer, and S3) into **any AWS account**.

## Getting Started

1. Clone this repo:

```bash
git clone <YOUR_FORK_OR_REPO_URL>
cd tier3-app
```

2. Change into the `Resources` directory:

```bash
cd Resources
```

3. Follow the detailed deployment guide in `DEPLOYMENT.md`. In short, you will:

- Configure the AWS CLI for your account.
- Edit `create-stack.sh` to set your **DB password**, optional **EC2 key pair name**, and (if needed) **region**.
- Run `./create-stack.sh` to create the CloudFormation stack, upload the PHP app to S3, and scale up the Auto Scaling Group.

All infrastructure is created in its own VPC, and you can safely tear everything down later by following the deletion steps in `DEPLOYMENT.md`.
