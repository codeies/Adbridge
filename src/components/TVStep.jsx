// components/TVStep.jsx
import { Button } from "@/components/ui/button";
import useCampaignStore from "@/stores/useCampaignStore";

const TVStep = () => {
  const { setCurrentStep, setCampaignType } = useCampaignStore();

  return (
    <div className="space-y-6">
      <div className="flex items-center space-x-4">
        <Button
          variant="outline"
          onClick={() => {
            setCurrentStep(1);
            setCampaignType(null);
          }}
          className="flex items-center"
        >
          ← Back
        </Button>
        <h2 className="text-2xl font-semibold">Select TV Channel</h2>
      </div>
      <p className="text-gray-600">TV channel selection interface will go here</p>
    </div>
  );
};

export default TVStep;