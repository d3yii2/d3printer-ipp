<?php

namespace d3yii2\d3printeripp\controllers;
// ===================================================================
// USAGE EXAMPLES
// ===================================================================
use yii\web\NotFoundHttpException;

/**
 * Example Controller showing how to use the Printer Manager
 */
class PrinterController extends \yii\web\Controller
{
    /**
     * Print a document
     */
    public function actionPrint()
    {
        try {
            // Get the printer manager component
            $printerIPP = Yii::$app->printerIPP;

            // Example document content (could be PDF, PostScript, etc.)
            $document = file_get_contents('/path/to/document.pdf');

            // Print options
            $options = [
                'job-name' => 'Test Document',
                'copies' => 2,
                'media' => 'iso_a4_210x297mm',
                'sides' => 'two-sided-long-edge'
            ];

            // Print to specific printer
            $result = $printerIPP->printBySlug('office_hp', $document, $options);

            if ($result['success']) {
                return $this->asJson([
                    'status' => 'success',
                    'job_id' => $result['job-id'],
                    'message' => 'Document sent to printer successfully'
                ]);
            } else {
                return $this->asJson([
                    'status' => 'error',
                    'message' => 'Failed to print document'
                ]);
            }

        } catch (\Exception $e) {
            return $this->asJson([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get printer health status
     */
    public function actionHealth(string $slug)
    {
        $printerIPP = Yii::$app->printerIPP;
        $health = $printerIPP->getHealthStatus($slug);

        return $this->asJson($health);
    }

    /**
     * Get specific printer status
     * @throws NotFoundHttpException
     */
    public function actionStatus($slug): \yii\web\Response
    {
        $printerIPP = Yii::$app->printerIPP;
        $printer = $printerIPP->getPrinter($slug);

        if (!$printer) {
            throw new \yii\web\NotFoundHttpException("Printer not found");
        }

        try {
            $status = [
                'online' => $printer->isOnline(),
                'status' => $printer->getStatus(),
                'supplies' => $printer->getSuppliesStatus(),
                'system_info' => $printer->getSystemInfo(),
                'jobs' => $printer->getJobs()
            ];

            return $this->asJson($status);

        } catch (\Exception $e) {
            return $this->asJson([
                'error' => $e->getMessage(),
                'online' => false
            ]);
        }
    }

    /**
     * Cancel a print job
     * @throws NotFoundHttpException
     */
    public function actionCancelJob($slug, $jobId): \yii\web\Response
    {
        $printerIPP = Yii::$app->printerIPP;
        $printer = $printerIPP->getPrinter($slug);

        if (!$printer) {
            throw new \yii\web\NotFoundHttpException('Printer not found');
        }

        $success = $printer->cancelJob((int)$jobId);

        return $this->asJson([
            'success' => $success,
            'message' => $success ? 'Job cancelled successfully' : 'Failed to cancel job'
        ]);
    }
}
