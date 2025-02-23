import { Button } from "@/components/ui/button";

const StepHeader = ({ title, onBack }) => {
    return (
        <div className="w-full border-b pb-4 mb-6">
            <div className="flex items-center justify-between max-w-screen-xl mx-auto">
                <Button
                    variant="outline"
                    onClick={onBack}
                    className="flex items-center hover:bg-gray-100"
                >
                    ← Back
                </Button>
                <h2 className="text-2xl font-semibold flex-1 text-center">{title}</h2>
                <div className="w-[72px]"></div>
            </div>
        </div>
    );
};

export default StepHeader;