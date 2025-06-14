<?php
// Database connection
$servername = "sql112.ezyro.com";
$username = "ezyro_39081039";
$password = "healthdata12345";
$dbname = "ezyro_39081039_healthdata";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get cluster metrics
$metrics_sql = "SELECT * FROM advanced_vitamin_clustering_metrics";
$metrics_result = $conn->query($metrics_sql);

// Get cluster assignments
$clusters_sql = "SELECT * FROM advanced_vitamin_clustering_results";
$clusters_result = $conn->query($clusters_sql);

// Get optimal K
$optimal_k_sql = "SELECT k FROM advanced_vitamin_clustering_metrics WHERE optimal_k = 1 LIMIT 1";
$optimal_k_result = $conn->query($optimal_k_sql);
$optimal_k_row = $optimal_k_result->fetch_assoc();
$optimal_k = $optimal_k_row['k'];

// Calculate cluster profiles
$cluster_profiles = array();
$cluster_counts = array();
$numeric_stats = array();
$categorical_stats = array();

// Initialize arrays
for ($i = 0; $i < $optimal_k; $i++) {
    $cluster_profiles[$i] = array();
    $cluster_counts[$i] = 0;
    $numeric_stats[$i] = array('age_in_months_sum' => 0, 'age_in_months_count' => 0);
    $categorical_stats[$i] = array(
        'gender' => array('Male' => 0, 'Female' => 0),
        'age_range_6_11_mons' => array('yes' => 0, 'no' => 0),
        'age_range_12_59_mons' => array('yes' => 0, 'no' => 0)
    );
}

// Process cluster data
if ($clusters_result->num_rows > 0) {
    while($row = $clusters_result->fetch_assoc()) {
        $cluster = $row['cluster'];
        $cluster_counts[$cluster]++;
        
        // Numeric features
        $numeric_stats[$cluster]['age_in_months_sum'] += $row['age_in_months'];
        $numeric_stats[$cluster]['age_in_months_count']++;
        
        // Categorical features
        $categorical_stats[$cluster]['gender'][$row['gender']]++;
        $categorical_stats[$cluster]['age_range_6_11_mons'][$row['age_range_6_11_mons']]++;
        $categorical_stats[$cluster]['age_range_12_59_mons'][$row['age_range_12_59_mons']]++;
    }
}

// Calculate final stats
for ($i = 0; $i < $optimal_k; $i++) {
    $cluster_profiles[$i]['count'] = $cluster_counts[$i];
    
    // Numeric stats
    $cluster_profiles[$i]['age_in_months_mean'] = 
        $numeric_stats[$i]['age_in_months_sum'] / $numeric_stats[$i]['age_in_months_count'];
    
    // Categorical stats (percentages)
    $total = $cluster_counts[$i];
    foreach ($categorical_stats[$i] as $feature => $values) {
        foreach ($values as $category => $count) {
            $cluster_profiles[$i][$feature . '_' . $category] = round(($count / $total) * 100, 2);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Data Clustering Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { margin-bottom: 20px; }
        .metric-card { background-color: #f8f9fa; }
        .optimal-card { background-color: #e7f5ff; }
        .profile-table th { background-color: #f1f1f1; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Vitamin A Data Clustering Results</h1>
        
        <!-- Cluster Metrics -->
        <div class="card metric-card">
            <div class="card-header">
                <h2>Cluster Evaluation Metrics</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>K</th>
                                <th>Inertia</th>
                                <th>Silhouette Score</th>
                                <th>Elbow Diff</th>
                                <th>Optimal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $metrics_result->fetch_assoc()): ?>
                            <tr <?= $row['optimal_k'] ? 'class="table-success"' : '' ?>>
                                <td><?= $row['k'] ?></td>
                                <td><?= number_format($row['inertia'], 2) ?></td>
                                <td><?= number_format($row['silhouette_score'], 4) ?></td>
                                <td><?= $row['elbow_diff'] ? number_format($row['elbow_diff'], 2) : 'N/A' ?></td>
                                <td><?= $row['optimal_k'] ? '✓' : '' ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Optimal Clusters -->
        <div class="card optimal-card">
            <div class="card-header">
                <h2>Optimal Cluster Selection</h2>
            </div>
            <div class="card-body">
                <p class="lead">The algorithm selected <strong><?= $optimal_k ?></strong> as the optimal number of clusters.</p>
            </div>
        </div>
        
        <!-- Cluster Distribution -->
        <div class="card">
            <div class="card-header">
                <h2>Cluster Distribution</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cluster</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = array_sum($cluster_counts);
                            foreach ($cluster_counts as $cluster => $count): 
                                $percentage = ($count / $total) * 100;
                            ?>
                            <tr>
                                <td><?= $cluster ?></td>
                                <td><?= $count ?></td>
                                <td><?= number_format($percentage, 2) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-secondary">
                                <td><strong>Total</strong></td>
                                <td><strong><?= $total ?></strong></td>
                                <td><strong>100%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Cluster Profiles -->
        <div class="card">
            <div class="card-header">
                <h2>Cluster Profiles</h2>
            </div>
            <div class="card-body">
                <?php foreach ($cluster_profiles as $cluster => $profile): ?>
                <div class="mb-4">
                    <h4>Cluster <?= $cluster ?></h4>
                    <div class="table-responsive">
                        <table class="table table-bordered profile-table">
                            <tr>
                                <th width="25%">Size</th>
                                <td><?= $profile['count'] ?> records (<?= number_format(($profile['count'] / $total) * 100, 2) ?>%)</td>
                            </tr>
                            <tr>
                                <th>Average Age (months)</th>
                                <td><?= number_format($profile['age_in_months_mean'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Gender Distribution</th>
                                <td>
                                    Male: <?= $profile['gender_Male'] ?>% | 
                                    Female: <?= $profile['gender_Female'] ?>%
                                </td>
                            </tr>
                            <tr>
                                <th>Age Range 6-11 months</th>
                                <td>
                                    Yes: <?= $profile['age_range_6_11_mons_yes'] ?>% | 
                                    No: <?= $profile['age_range_6_11_mons_no'] ?>%
                                </td>
                            </tr>
                            <tr>
                                <th>Age Range 12-59 months</th>
                                <td>
                                    Yes: <?= $profile['age_range_12_59_mons_yes'] ?>% | 
                                    No: <?= $profile['age_range_12_59_mons_no'] ?>%
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>