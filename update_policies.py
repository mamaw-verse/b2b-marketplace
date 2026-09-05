import os

policies = {
    'OrderPolicy.php': 'orders_enabled',
    'DisputePolicy.php': 'disputes_enabled',
    'PayoutPolicy.php': 'payouts_enabled',
    'QuotationPolicy.php': 'quotations_enabled',
    'ReviewPolicy.php': 'reviews_enabled',
    'RfqPolicy.php': 'seller_rfq_queue_enabled',
}

for filename, feature in policies.items():
    filepath = os.path.join('app/Policies', filename)
    with open(filepath, 'r') as f:
        content = f.read()

    # We want to insert this at the top of every public function
    lines = content.split('\n')
    new_lines = []
    for line in lines:
        new_lines.append(line)
        if line.strip().startswith('public function') and not line.strip().endswith(';'):
            # This is a bit brittle, wait, let's inject it inside the body.
            pass

    # A better way: replace "public function METHOD_NAME(...) \n {" with
    # "public function METHOD_NAME(...) \n {\n if (!config('marketplace.features.FEATURE')) return false;"
    
