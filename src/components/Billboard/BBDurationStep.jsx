import { Card, CardContent } from "@/components/ui/card";
import useCampaignStore from "@/stores/useCampaignStore";
import StepHeader from "@/components/StepHeader";

const BBDurationStep = () => {
    const { billboard, setCurrentStep, setBillboardFilters } = useCampaignStore();

    const durations = [
        { key: 'daily', label: 'Daily' },
        { key: 'daily_premium', label: 'Daily Premium' },
        { key: 'weekly', label: 'Weekly' },
        { key: 'weekly_premium', label: 'Weekly Premium' },
        { key: 'monthly', label: 'Monthly' },
        { key: 'monthly_premium', label: 'Monthly Premium' },
    ];
    const handleBack = () => {
        setCurrentStep(2);
        //setCampaignType(null);
    };

    return (
        <div className="space-y-6">
            <StepHeader
                title="Select Duration"
                onBack={handleBack}
            />

            <div className="grid grid-cols-3 gap-4">
                {durations.map(({ key, label }) => (
                    <Card
                        key={key}
                        className="cursor-pointer hover:bg-gray-50"
                        onClick={() => {
                            setBillboardFilters({ selectedDuration: key });
                            setCurrentStep(4); // Move to the next step
                        }}
                    >
                        <CardContent className="p-4">
                            <h3 className="font-semibold">{label}</h3>
                            <p className="text-sm text-gray-600">${billboard.selectedBillboard?.pricing[key]} / {label.toLowerCase()}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

export default BBDurationStep;
