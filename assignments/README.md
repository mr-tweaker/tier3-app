# CloudFormation Assignments

This directory contains CloudFormation templates for 5 AWS infrastructure assignments.

## Assignment 1: Basic VPC, EC2, and S3
**File:** `assignment1-basic-vpc-ec2-s3.yaml`

Creates a basic VPC with a public subnet, EC2 instance, and S3 bucket. Includes:
- VPC with DNS support
- Public subnet with auto-assign public IP
- EC2 instance using latest Amazon Linux 2 AMI from SSM Parameter Store
- S3 bucket
- Security group allowing SSH (port 22)
- Parameter groups and Banking project tags

**Parameters:**
- `VpcCidr` - VPC CIDR block (default: 10.0.0.0/16)
- `SubnetCidr` - Subnet CIDR block (default: 10.0.1.0/24)
- `InstanceType` - EC2 instance type (t2.micro, t3.micro, or t3.small)
- `KeyName` - EC2 Key Pair name for SSH access
- `BucketName` - Globally unique S3 bucket name

---

## Assignment 2: SSM Latest AMI
**File:** `assignment2-ssm-latest-ami.yaml`

Simple EC2 instance template demonstrating account and region-agnostic AMI lookup using SSM Parameter Store.

**Parameters:**
- `InstanceType` - EC2 instance type (default: t3.micro)
- `KeyName` - EC2 Key Pair name

**Key Feature:** Uses `{{resolve:ssm:/aws/service/ami-amazon-linux-latest/amzn2-ami-hvm-x86_64-gp2}}` for automatic latest AMI selection.

---

## Assignment 3: Highly Available 3-Tier Application (eu-west-2)
**File:** `assignment3-ha-3tier-eu-west-2.yaml`

Creates a highly available 3-tier architecture in eu-west-2 with:
- VPC with public and private subnets across 2 AZs
- Application Load Balancer (ALB) in public subnets
- Web tier Auto Scaling Group (ASG) in public subnets
- App tier ASG in private subnets
- RDS database (MySQL or PostgreSQL) in private DB subnets with Multi-AZ
- Security groups with proper tier isolation

**Parameters:**
- `VpcCidr` - VPC CIDR (default: 10.0.0.0/16)
- `PublicSubnet1Cidr`, `PublicSubnet2Cidr` - Public subnet CIDRs
- `AppSubnet1Cidr`, `AppSubnet2Cidr` - Application tier subnet CIDRs
- `DbSubnet1Cidr`, `DbSubnet2Cidr` - Database tier subnet CIDRs
- `WebInstanceType`, `AppInstanceType` - EC2 instance types
- `KeyName` - EC2 Key Pair name
- `DbUsername`, `DbPassword` - RDS credentials
- `DbEngine` - Database engine (mysql or postgres)

---

## Assignment 4: ECS Fargate StackSet Template
**File:** `assignment4-ecs-stackset-template.yaml`

Reusable ECS Fargate service template designed for CloudFormation StackSets. Creates:
- ECS cluster
- Fargate task definition
- ECS service with configurable desired count
- Application Load Balancer
- Task execution role

**Parameters:**
- `VpcId` - VPC ID where service will run
- `PublicSubnets` - List of public subnet IDs (at least 2 AZs)
- `ContainerImage` - Container image URI
- `ServiceDesiredCount` - Number of tasks (default: 2, min: 1, max: 10)

---

## Assignment 5: Cross-Account IAM Role
**File:** `assignment5-cross-account-iam-role.yaml`

Creates a cross-account IAM role with least privilege permissions for:
- EC2 instance management (describe, start, stop)
- RDS instance management (describe, start, stop)
- KMS key usage (encrypt, decrypt, generate data key)

**Parameters:**
- `ExternalAccountId` - AWS Account ID that can assume this role (12 digits)
- `Ec2InstanceArn` - ARN of EC2 instance (or pattern) this role can manage
- `RdsInstanceArn` - ARN of RDS instance this role can manage
- `KmsKeyArn` - ARN of KMS key this role can use
- `ExternalId` - Optional external ID for added security (can be empty)

**Security Features:**
- Least privilege permissions scoped to specific resources
- Optional external ID condition
- 1-hour maximum session duration

---

## Usage

Each template can be deployed using AWS CLI or CloudFormation Console:

```bash
aws cloudformation create-stack \
  --stack-name <stack-name> \
  --template-body file://assignments/assignment<N>.yaml \
  --parameters ParameterKey=...,ParameterValue=... \
  --region <region>
```

Or via AWS Console: Upload the template file and provide parameters as prompted.
