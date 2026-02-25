#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATE="${SCRIPT_DIR}/3tier-app-cloudformation.yaml"
WEBAPP_DIR="${SCRIPT_DIR}/webapp"
STACK_NAME="tier3-web-app"
REGION="us-east-1"

if [[ ! -f "$TEMPLATE" ]]; then
  echo "Error: Template not found: $TEMPLATE"
  exit 1
fi

for f in index.php add-user.php users.php; do
  if [[ ! -f "${WEBAPP_DIR}/${f}" ]]; then
    echo "Error: Required file not found: ${WEBAPP_DIR}/${f}"
    exit 1
  fi
done

echo "===> Creating stack $STACK_NAME (ASG desired capacity 0 so app files can be uploaded first)..."
aws cloudformation create-stack \
  --stack-name "$STACK_NAME" \
  --template-body "file://${TEMPLATE}" \
  --parameters \
    ParameterKey=DBPassword,ParameterValue='CHANGE_ME_DB_PASSWORD' \
    ParameterKey=DBUsername,ParameterValue=admin \
    ParameterKey=KeyPairName,ParameterValue='' \
    ParameterKey=ASGDesiredCapacity,ParameterValue=0 \
  --capabilities CAPABILITY_NAMED_IAM \
  --region "$REGION"

echo "===> Waiting for stack create to complete..."
aws cloudformation wait stack-create-complete --stack-name "$STACK_NAME" --region "$REGION"

BUCKET=$(aws cloudformation describe-stacks \
  --stack-name "$STACK_NAME" \
  --region "$REGION" \
  --query "Stacks[0].Outputs[?OutputKey=='AppBucketName'].OutputValue" \
  --output text)

if [[ -z "$BUCKET" || "$BUCKET" == "None" ]]; then
  echo "Error: Could not get AppBucketName from stack outputs"
  exit 1
fi

echo "===> Uploading app files to s3://$BUCKET ..."
aws s3 cp "${WEBAPP_DIR}/index.php" "s3://${BUCKET}/index.php" --region "$REGION"
aws s3 cp "${WEBAPP_DIR}/add-user.php" "s3://${BUCKET}/add-user.php" --region "$REGION"
aws s3 cp "${WEBAPP_DIR}/users.php" "s3://${BUCKET}/users.php" --region "$REGION"

echo "===> Updating stack to set ASG desired capacity to 2..."
aws cloudformation update-stack \
  --stack-name "$STACK_NAME" \
  --use-previous-template \
  --parameters \
    ParameterKey=DBPassword,ParameterValue='CHANGE_ME_DB_PASSWORD' \
    ParameterKey=DBUsername,ParameterValue=admin \
    ParameterKey=KeyPairName,ParameterValue='' \
    ParameterKey=ASGDesiredCapacity,ParameterValue=2 \
  --region "$REGION" \
  --capabilities CAPABILITY_NAMED_IAM 2>/dev/null || true

echo "===> Waiting for stack update to complete (if any)..."
aws cloudformation wait stack-update-complete --stack-name "$STACK_NAME" --region "$REGION" 2>/dev/null || true

echo ""
echo "Stack is ready."
echo "Load Balancer URL: $(aws cloudformation describe-stacks --stack-name "$STACK_NAME" --region "$REGION" --query "Stacks[0].Outputs[?OutputKey=='LoadBalancerURL'].OutputValue" --output text)"
echo "App bucket: $BUCKET"

