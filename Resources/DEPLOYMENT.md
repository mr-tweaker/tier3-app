# Deploying the 3-Tier Web App on AWS

This guide walks you through deploying the 3-tier web application (VPC, RDS MySQL, EC2 Auto Scaling, ALB, S3) on **any AWS account** using the CloudFormation template and scripts in this folder.

If you cloned this from GitHub, you already have everything under `Resources/`. Just follow the steps below from this directory.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Files You Need](#2-files-you-need)
3. [One-Time Setup](#3-one-time-setup)
4. [Deployment Options](#4-deployment-options)
5. [Option A: Deploy with the Script (Recommended)](#option-a-deploy-with-the-script-recommended)
6. [Option B: Deploy Manually](#option-b-deploy-manually)
7. [After Deployment](#7-after-deployment)
8. [Troubleshooting](#8-troubleshooting)
9. [Deleting the Stack](#9-deleting-the-stack)

---

## 1. Prerequisites

- **AWS account** with permissions to create: VPC, EC2, RDS, S3, IAM roles/policies, CloudFormation stacks.
- **AWS CLI** installed and configured with credentials that can create the above resources.
  - Install: [AWS CLI v2](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html)
  - Configure: `aws configure` (Access Key, Secret Key, default region, e.g. `us-east-1`)
- **Bash** (Linux/macOS or WSL on Windows) to run the deployment script.

---

## 2. Files You Need

Ensure you have this folder structure (all files from the `Resources` folder):

```
Resources/
├── 3tier-app-cloudformation.yaml   # CloudFormation template
├── create-stack.sh                  # Deployment script
├── webapp/
│   ├── index.php                    # Home page
│   ├── add-user.php                 # Add user form
│   └── users.php                    # List/delete users
└── DEPLOYMENT.md                    # This file
```

Do **not** remove or rename `webapp/` or the three PHP files; the script uploads them to S3 during deployment.

---

## 3. One-Time Setup

### 3.1 AWS CLI configured

```bash
aws configure
# Enter: Access Key ID, Secret Access Key, Default region (e.g. us-east-1)
```

Verify:

```bash
aws sts get-caller-identity
```

You should see your account ID and user/role ARN.

### 3.2 (Optional) EC2 Key Pair for SSH

If you want to SSH into the EC2 instances:

1. In AWS Console: **EC2 → Key Pairs → Create key pair** (e.g. name: `my-3tier-key`, type: RSA, format: `.pem`).
2. Download and store the `.pem` file securely.
3. When deploying, use the **key pair name only** (e.g. `my-3tier-key`), **not** the filename like `my-3tier-key.pem`.

If you skip this, you can leave the key pair parameter empty; the stack will still deploy, but you won’t have SSH access to the instances.

### 3.3 Choose a database password

Pick a strong password for the RDS MySQL database:

- Length: 8–41 characters  
- Allowed: letters, numbers, and `!@#$%^&*()_+=-`  
- You’ll need this only if you ever connect to the DB directly; the app uses it automatically.

---

## 4. Deployment Options

| Option | Best for |
|--------|----------|
| **A: Run the script** | Easiest: one command, script creates stack, uploads app files, then scales up. |
| **B: Manual steps** | Learning, or if you want to change parameters (region, stack name, etc.) step by step. |

---

## Option A: Deploy with the Script (Recommended)

### Step 1: Edit the script parameters (if needed)

Open `create-stack.sh` and adjust these to match **your** account:

| Variable / Parameter | What to set |
|----------------------|-------------|
| `REGION`             | Default `us-east-1`. Change if you want another region (e.g. `us-west-2`). |
| `ParameterKey=DBPassword,ParameterValue=...` | Your chosen RDS password (same in both create-stack and update-stack blocks). |
| `ParameterKey=DBUsername,ParameterValue=...` | Default `admin`. Change only if you want a different DB user. |
| `ParameterKey=KeyPairName,ParameterValue=...` | Your EC2 key pair **name** (e.g. `my-3tier-key`), or leave as empty string `''` to skip SSH. |

Example for a different region and key pair:

```bash
REGION="us-west-2"
# ...
ParameterKey=KeyPairName,ParameterValue=my-3tier-key
```

If you use a **password with special characters** (e.g. `!`), keep it in single quotes:  
`ParameterValue='MyPass123!@'`

### Step 2: Make the script executable

```bash
cd Resources
chmod +x create-stack.sh
```

### Step 3: Run the script

```bash
./create-stack.sh
```

What the script does:

1. Creates the CloudFormation stack with **ASG desired capacity = 0** (no EC2 instances yet).
2. Waits for the stack to reach `CREATE_COMPLETE`.
3. Gets the S3 bucket name from the stack outputs.
4. Uploads `webapp/index.php`, `webapp/add-user.php`, and `webapp/users.php` to that bucket.
5. Updates the stack to set **ASG desired capacity = 2** so instances launch.
6. Waits for the update to complete (if applicable).
7. Prints the **Load Balancer URL** and app bucket name.

Total time is typically **15–25 minutes** (VPC, RDS, and instances take time).

### Step 4: Open the app

When the script finishes, it prints something like:

```text
Load Balancer URL: http://tier3-app-alb-xxxxxxxxx.us-east-1.elb.amazonaws.com
```

Open that URL in a browser. You should see:

- **Home**: 3-tier app info, DB status, visit count.
- **View Users**: list of users from RDS (initially empty).
- **Add User**: form to add users (stored in RDS).

If the page doesn’t load immediately, wait 2–3 minutes for instances to pass the ALB health check, then refresh.

---

## Option B: Deploy Manually

Use this if you prefer the AWS Console or want to run individual commands yourself.

### B.1 Create the stack (capacity 0)

From the directory that contains `3tier-app-cloudformation.yaml`:

```bash
aws cloudformation create-stack \
  --stack-name tier3-web-app \
  --template-body file://3tier-app-cloudformation.yaml \
  --parameters \
    ParameterKey=DBPassword,ParameterValue='YOUR_DB_PASSWORD' \
    ParameterKey=DBUsername,ParameterValue=admin \
    ParameterKey=KeyPairName,ParameterValue=YOUR_KEY_PAIR_NAME \
    ParameterKey=ASGDesiredCapacity,ParameterValue=0 \
  --capabilities CAPABILITY_NAMED_IAM \
  --region us-east-1
```

- Replace `YOUR_DB_PASSWORD` with your RDS password (single quotes if it has special characters).
- Replace `YOUR_KEY_PAIR_NAME` with your EC2 key pair name, or use `ParameterKey=KeyPairName,ParameterValue=''` for no SSH.

### B.2 Wait for stack creation

```bash
aws cloudformation wait stack-create-complete --stack-name tier3-web-app --region us-east-1
```

### B.3 Get the S3 bucket name

```bash
aws cloudformation describe-stacks \
  --stack-name tier3-web-app \
  --region us-east-1 \
  --query "Stacks[0].Outputs[?OutputKey=='AppBucketName'].OutputValue" \
  --output text
```

Note the bucket name (e.g. `tier3-web-app-appbucket-xxxxxxxxx`).

### B.4 Upload the app files to S3

```bash
BUCKET=<paste-the-bucket-name-here>
REGION=us-east-1

aws s3 cp webapp/index.php    "s3://${BUCKET}/index.php"    --region "$REGION"
aws s3 cp webapp/add-user.php "s3://${BUCKET}/add-user.php" --region "$REGION"
aws s3 cp webapp/users.php    "s3://${BUCKET}/users.php"    --region "$REGION"
```

### B.5 Scale up the Auto Scaling Group

```bash
aws cloudformation update-stack \
  --stack-name tier3-web-app \
  --use-previous-template \
  --parameters \
    ParameterKey=DBPassword,ParameterValue='YOUR_DB_PASSWORD' \
    ParameterKey=DBUsername,ParameterValue=admin \
    ParameterKey=KeyPairName,ParameterValue=YOUR_KEY_PAIR_NAME \
    ParameterKey=ASGDesiredCapacity,ParameterValue=2 \
  --region us-east-1 \
  --capabilities CAPABILITY_NAMED_IAM
```

Then wait:

```bash
aws cloudformation wait stack-update-complete --stack-name tier3-web-app --region us-east-1
```

### B.6 Get the app URL

```bash
aws cloudformation describe-stacks \
  --stack-name tier3-web-app \
  --region us-east-1 \
  --query "Stacks[0].Outputs[?OutputKey=='LoadBalancerURL'].OutputValue" \
  --output text
```

Open that URL in your browser.

---

## 7. After Deployment

### Stack outputs (useful values)

From **CloudFormation → Stacks → tier3-web-app → Outputs** (or via CLI as in B.6):

| Output            | Description                    |
|-------------------|--------------------------------|
| LoadBalancerURL   | App URL (use this in the browser). |
| LoadBalancerDNS   | ALB DNS name.                  |
| RDSEndpoint       | MySQL host (for direct DB access). |
| AppBucketName     | S3 bucket with the PHP files.  |
| VPCId             | VPC created for the stack.     |

### App behavior

- **Home** (`/` or `/index.php`): Shows instance info, DB status, and visit count.
- **View Users** (`/users.php`): Lists users from RDS; supports delete.
- **Add User** (`/add-user.php`): Form to add users (name, email) to RDS.

Data is stored in MySQL (RDS); all app instances share the same database.

---

## 8. Troubleshooting

### Script fails: "Template not found" or "Required file not found"

- Run the script from the **Resources** directory, or use the full path to the script.
- Ensure `3tier-app-cloudformation.yaml` and `webapp/index.php`, `webapp/add-user.php`, `webapp/users.php` exist.

### Script fails: "Key pair does not exist"

- The **Key pair name** in the script/parameters must match an existing EC2 key pair in the **same region** you’re deploying to (e.g. `us-east-1`).
- Use only the key pair **name** (e.g. `my-key`), not the file name (e.g. `my-key.pem`).
- Or set `ParameterKey=KeyPairName,ParameterValue=''` to deploy without a key pair.

### Script fails: "Bucket you tried to delete is not empty"

- This happens when **deleting** the stack, not during deploy. See [Deleting the Stack](#9-deleting-the-stack).

### Load Balancer URL returns 503 or doesn’t load

- New instances need a few minutes to pass the ALB health check (`/health.html`). Wait 3–5 minutes and try again.
- In EC2 → Target Groups → tier3-app-tg, check that targets become **Healthy**.

### App shows "Database connection failed"

- RDS takes 5–10 minutes to become available after stack creation. If you scaled up immediately, wait and refresh.
- Ensure you didn’t change the DB name in the template; the app expects database name `appdb`.

### Using a different region

- Change `REGION` in the script (or use `--region` in every AWS CLI command).
- Create or select an EC2 key pair in **that** region if you use one.

### "Insufficient capacity" or quota errors

- You may hit account limits (e.g. VPCs, EC2, RDS). Request a quota increase in Service Quotas, or use a region where you have capacity.

---

## 9. Deleting the Stack

To remove all resources (VPC, RDS, EC2, ALB, S3 bucket, etc.):

1. **Empty the S3 app bucket** (CloudFormation cannot delete a non-empty bucket):

   ```bash
   BUCKET=$(aws cloudformation describe-stacks --stack-name tier3-web-app --region us-east-1 \
     --query "Stacks[0].Outputs[?OutputKey=='AppBucketName'].OutputValue" --output text)
   aws s3 rm "s3://${BUCKET}/" --recursive --region us-east-1
   ```

2. **Delete the stack**:

   ```bash
   aws cloudformation delete-stack --stack-name tier3-web-app --region us-east-1
   ```

3. **Wait for deletion** (optional):

   ```bash
   aws cloudformation wait stack-delete-complete --stack-name tier3-web-app --region us-east-1
   ```

If the bucket is not emptied first, the stack will stall on `AppBucket` with `DELETE_FAILED`. Empty the bucket (step 1), then run delete again; CloudFormation will retry and complete.

---

## Summary

| Goal                    | Action |
|-------------------------|--------|
| Deploy on any AWS account | Clone the repo, `cd Resources`, set DB password and optional key pair in `create-stack.sh`, then run `./create-stack.sh`. |
| Use a different region  | Set `REGION` in the script (and create key pair in that region if needed). |
| Deploy without SSH      | Set `ParameterKey=KeyPairName,ParameterValue=''` in the script. |
| Access the app          | Use the **LoadBalancerURL** from stack outputs. |
| Tear down               | Empty the S3 app bucket, then delete the stack. |

For the smoothest experience, use **Option A** (the script) and only switch to **Option B** if you need to customize steps or use the console.

